<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Gateway_GatewayController extends Mage_Core_Controller_Front_Action
{
    /**
     * Form the redirect form and POST to the IPGPAY Secure Payment Form
     */
    public function redirectAction()
    {
        //Magento uses "increment_id" for customer facing order numbers, which is different to the "order_id" aka "entity_id"
        $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();


        $_order = new Mage_Sales_Model_Order(); //object to get all order information
        $_order->loadByIncrementId($incrementId);
        $payment = $_order->getPayment();

        $form_fields = $this->mergeFormFields(
            $this->getCustomerInformation($_order),
            $this->getOrderItems($_order),
            $this->getGatewayParameters($_order)
        );

        $gateway_url = preg_replace('#/payment/form/post#i', '', Mage::getStoreConfig('payment/paymentgateway/payment_form_url')) . '/payment/form/post';

        $html = '';
        $html .= '<!DOCTYPE html>';
        $html .= '<html>';
        $html .= '<head>';
        $html .= '<title>Redirecting to payment gateway...</title>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= '<div class="page-title">';
        $html .= '<h1>Redirecting to payment gateway...</h1>';
        $html .= '</div>';
        $html .= '<form name="mygatewayform" method="post" action="' . htmlentities($gateway_url, ENT_QUOTES, 'UTF-8') . '">';
        foreach ($form_fields as $field => $value) {
            $html .= '<input type="hidden" name="' . htmlentities($field, ENT_QUOTES, 'UTF-8') . '" value="' . htmlentities($value, ENT_QUOTES, 'UTF-8') . '">';
        }
        $html .= '</form>';
        $html .= '<script type="text/javascript">';
        $html .= 'document.mygatewayform.submit();';
        $html .= '</script>';
        $html .= '</body>';
        $html .= '</html>';
        echo $html;
    }


    /**
     * Customer will be returned here after checkout on the Secure Payment Form
     */
    public function successAction()
    {
        $this->_redirect('checkout/onepage/success', ['_secure' => true]);
    }

    /**
     * Customer will be returned here if payment is unsuccessful
     */
    public function cancelAction()
    {
        $lastRealOrderId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        if ($lastRealOrderId) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($lastRealOrderId);
            if ($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(
                    Mage_Sales_Model_Order::STATE_CANCELED,
                    true,
                    'Gateway has declined the payment.'
                )->save();
            }
        }

        $this->_redirect('checkout/cart');
    }
    
    /**
     * Customer will be returned here if returning from payment form
     */
    public function cancelAction()
    {
        $this->_redirect('checkout/cart');
    }

    /**
     * Handle notifications from the IPGPAY gateway
     */
    public function notifyAction()
    {
        $fields = [];
        $signature = $_REQUEST['PS_SIGNATURE'];
        foreach ($_REQUEST as $key => $value) {
            if ($key != 'PS_SIGNATURE') {
                $fields[$key] = $value;
            }
        }

        if ($signature != $this->createSignature($fields)) {
            die('Invalid signature. Aborting!');
        }

        if (!isset($fields['notification_type'])) {
            die('Invalid notification format');
        }

        if (!in_array($fields['notification_type'], ['order', 'orderfailure', 'void', 'settle', 'credit', 'rebillsuccess', 'orderfailure'])) {
            die('OK'); //Ignore other notifications
        }

        unset($fields['PS_EXPIRETIME']);
        unset($fields['PS_SIGTYPE']);

        $incrementId = $fields['order_reference'];
        /** @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->loadByIncrementId($incrementId);;
        /** @var $payment Mage_Sales_Model_Order_Payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($fields['trans_id']);

        $AddComments = false;

        if (isset($fields['trans_type'])) {
            switch ($fields['trans_type']) {
                case 'auth':
                    $payment->setAdditionalData(serialize($fields));
                    $payment->setCcTransId($fields['trans_id']);
                    $payment->setIsTransactionClosed(0);
                    $transaction = $payment->getTransaction($fields['trans_id']);
                    if (!$transaction) {
                        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
                        $AddComments = true;
                    }
                    $this->addTransactionDetails($transaction, $fields);
                    break;
                case 'sale':
                    $payment->setAdditionalData(serialize($fields));
                    $payment->setCcTransId($fields['trans_id']);
                    $payment->setIsTransactionClosed(1);
                    $transaction = $payment->getTransaction($fields['trans_id']);
                    if (!$transaction) {
                        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER);
                        $AddComments = true;
                    }
                    $this->addTransactionDetails($transaction, $fields);
                    break;
            }
        }

        $extra_info_fmt = '';
        if (isset($fields['response'])) {
            $extra_info_fmt .= "Response: {$fields['response']} ";
        }

        if (isset($fields['response_code'])) {
            $extra_info_fmt .= "Response Code: {$fields['response_code']} ";
        }

        if (isset($fields['response_text'])) {
            $extra_info_fmt .= "Response Text: {$fields['response_text']} ";
        }

        if (isset($fields['trans_id'])) {
            $extra_info_fmt .= "Transaction ID: {$fields['trans_id']} ";
        }

        if (isset($fields['order_id'])) {
            $extra_info_fmt .= "IPGPAY Order ID: {$fields['order_id']} ";
        }


        if (isset($fields['trans_type'])) {
            $extra_info_fmt .= "Transaction Type: {$fields['trans_type']} ";
        }

        if (isset($fields['auth_code'])) {
            $extra_info_fmt .= "Auth Code: {$fields['auth_code']} ";
        }

        $invoices = $order->getInvoiceCollection();
        $has_invoice = count($invoices) > 0;

        $response = $fields['notification_type'];
        switch ($response) {
            case 'order':
                if ($fields['trans_type'] == 'sale') {
                    $OrderState = Mage_Sales_Model_Order::STATE_PROCESSING;
                    if (!$has_invoice) {
                        $this->createInvoice($order, $fields['trans_id']);
                    }
                } elseif ($fields['trans_type'] == 'auth') {
                    $OrderState = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
                }
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Approved', $OrderState, true);
                $order->queueNewOrderEmail();
                $order->setEmailSent(true);
                $order->addStatusHistoryComment('Order email sent to customer');
                $order->save();
                break;
            case 'void':
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Voided', $order->getState(), $AddComments);
                break;
            case 'settle':
                $payment->setAdditionalData(serialize($fields));
                $payment->setCcTransId($fields['trans_id']);
                $payment->setIsTransactionClosed(1);
                $transaction = $payment->getTransaction($fields['trans_id']);
                if (!$transaction) {
                    $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
                    $AddComments = true;
                }
                $this->addTransactionDetails($transaction, $fields);
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Settled', Mage_Sales_Model_Order::STATE_PROCESSING, $AddComments);

                if (!$has_invoice) {
                    $this->createInvoice($order, $fields['trans_id']);
                }

                break;
            case 'orderpending': // Pending
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Pending', Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
                $order->queueNewOrderEmail();
                $order->setEmailSent(true);
                $order->addStatusHistoryComment('Order email sent to customer');
                $order->save();
                break;
            case 'credit':
                $payment->setCcTransId($fields['trans_id']);
                $transaction = $payment->getTransaction($fields['trans_id']);
                if (!$transaction) {
                    $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
                    $AddComments = true;
                }
                $this->addTransactionDetails($transaction, $fields);
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Credited', $order->getState(), $AddComments);
                //If credit was done in Magento, state may be moved to closed if whole order has been credited.
                //The gateway supports credits on rebills which doesn't mean the order should be closed.
                break;
            case 'rebillsuccess':
                $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);
                $this->addTransactionDetails($transaction, $fields);
                $this->modifyOrderPayment($order, $extra_info_fmt, 'Approved', $order->getState());
                break;
            case 'orderfailure':
                $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
                $this->addTransactionDetails($transaction, $fields);
                $this->setStateWithoutProtection($order, Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, 'Order Abandoned');
                break;
        }

        $payment->save();

        printf("%s\n", isset($_GET['response']) ? $_GET['response'] : 'OK');
        die();
    }

    /**
     * Add the transaction details to the transaction details page in Magento
     *
     * @param $transaction Mage_Sales_Model_Order_Payment_Transaction
     * @param $data
     */
    private function addTransactionDetails($transaction, $data)
    {
        $transaction->setAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
            $data)->save();
    }

    /**
     * Create an invoice for order
     *
     * @param $order
     * @return mixed
     */
    private function createInvoice($order, $TransactionID)
    {
        // sale is automatically captured, create invoice.
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($TransactionID);
        $invoice->register()->pay();

        Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();

        return $invoice;
    }

    /**
     * Update order state with a comment
     *
     * @param $order Mage_Sales_Model_Order
     * @param $extra_info
     * @param $state_text
     * @param $state_code
     */
    private function modifyOrderPayment($order, $extra_info, $state_text, $state_code, $addComments = true)
    {
        $message = sprintf("IPGPAY Payment: %s\n\n", $state_text) . $extra_info;
        $order->setState($state_code, $addComments, $message, false);
        $order->save();
    }

    /**
     * Update order state to protected statuses such as cancelled
     *
     * @param $order Mage_Sales_Model_Order
     */
    private function setStateWithoutProtection($order, $state, $status = false, $comment = '')
    {
        $order->setData('state', $state);
        if ($status) {
            if ($status === true) {
                $status = Mage::getSingleton('sales/order_config')->getStateDefaultStatus($state);
            }
            $order->setStatus($status);
            $history = $order->addStatusHistoryComment($comment, false); // no sense to set $status again
            $history->setIsCustomerNotified(false); // for backwards compatibility
        }

        $order->save();
    }

    /**
     * Massage the street address
     *
     * @param $str
     * @return array
     */
    private function splitStreetAddress($str)
    {
        $streets = explode("\n", $str);
        $street_1 = $str;
        $street_2 = '';
        if (count($streets) > 1) {
            $street_1 = $streets[0];
            $street_2 = $streets[1];
        }
        return [$street_1, $street_2];
    }

    /**
     * Get customer info
     *
     * @param $order
     * @return array
     */
    private function getCustomerInformation($order)
    {
        $billing_address = $order->getBillingAddress();
        $shipping_address = $order->getShippingAddress();

        $customer = [
            'customer_first_name' => $billing_address->getFirstname(),
            'customer_last_name' => $billing_address->getLastname(),
            'customer_address' => $billing_address->getStreet(1),
            'customer_address2' => $billing_address->getStreet(2),
            'customer_company' => $billing_address->company,
            'customer_city' => $billing_address->getCity(),
            'customer_state' => $billing_address->getRegion(),
            'customer_postcode' => $billing_address->getPostcode(),
            'customer_country' => $billing_address->getCountry(),
            'customer_email' => $billing_address->getEmail(),
            'customer_phone' => $billing_address->getTelephone(),

            'shipping_first_name' => $shipping_address->getFirstname(),
            'shipping_last_name' => $shipping_address->getLastname(),
            'shipping_address' => $shipping_address->getStreet(1),
            'shipping_address2' => $shipping_address->getStreet(2),
            'shipping_company' => $shipping_address->company,
            'shipping_city' => $shipping_address->getCity(),
            'shipping_state' => $shipping_address->getRegion(),
            'shipping_postcode' => $shipping_address->getPostcode(),
            'shipping_country' => $shipping_address->getCountry(),
            'shipping_email' => $shipping_address->getEmail(),
            'shipping_phone' => $shipping_address->getTelephone(),
        ];

        return $customer;
    }

    /**
     * Get the items on an order, including shipping and taxes and include them
     * as line items on the payment form
     *
     * @param $order
     * @return array
     */
    private function getOrderItems($order)
    {
        $currency = $order->getOrderCurrencyCode();

        $sa = $order->getShippingAddress();
        $ba = $order->getBillingAddress();

        $_shippingTax = $sa->getBaseTaxAmount();
        $_billingTax = $ba->getBaseTaxAmount();
        $tax = sprintf('%.2f', $_shippingTax + $_billingTax);

        $shippingCost = $order->getShippingAmount();
        $taxCost = $order->getTaxAmount();

        $discount = 0;

        $items = $order->getAllItems();
        $form_items = [];
        $idx = 0;
        foreach ($items as $item) {
            if ($item->getQtyToShip() < 1) continue;

            $product = $item->getProduct();
            $form_items[] = $this->getItemArray(
                ++$idx,
                $product->getSKU(),
                $product->getName(),
                $product->getDescription(),
                $item->getQtyToShip(),
                $item->is_virtual ? '1' : '0',
                $item->getPrice(),
                $currency,
                false,
                Mage::getStoreConfig('payment/paymentgateway/merchant_rebilling') == '1' ? '1' : '0'
            );

            $discount -= $item->getDiscountAmount();
        }

        if ($shippingCost > 0) {
            $form_items[] = $this->getItemArray(++$idx, null, 'Shipping', 'Shipping and handling', '1', '1', $shippingCost, $currency);
        }

        if ($taxCost > 0) {
            $form_items[] = $this->getItemArray(++$idx, null, 'Tax', '', '1', '1', $taxCost, $currency);
        }

        if ($discount < 0) {
            $form_items[] = $this->getItemArray(++$idx, null, 'Discount', '', '1', '1', $discount, $currency, true);
        }
        return $form_items;
    }

    /**
     * Get item parameters
     *
     * @param $idx
     * @param $code
     * @param $name
     * @param $description
     * @param $qty
     * @param $digital
     * @param $price
     * @param $currency
     * @param bool $discount
     * @return array
     */
    private function getItemArray($idx, $code = null, $name, $description, $qty, $digital, $price, $currency, $discount = false, $rebill = false)
    {
        if (in_array($currency, ["JPY", "VND", "KRW"])) {
            $parts = explode('.', $price);
            $price = $parts[0];
        } else {
            $price = sprintf('%.02f', $price);
        }

        $prefix = sprintf('item_%d', $idx);
        $ret = [
            $prefix . '_name' => $name,
            //$prefix . '_description' => $description,
            $prefix . '_qty' => $qty,
            $prefix . '_digital' => $digital,
            $prefix . '_unit_price_' . $currency => $price,
        ];

        if (isset($code)) {
            $ret[$prefix . '_code'] = $code;
        }

        if ($discount) {
            $ret[$prefix . '_discount'] = '1';
        }

        if ($rebill) {
            $ret[$prefix . '_rebill'] = '2';
        }

        return $ret;
    }

    /**
     * Get configuration parameters
     *
     * @param $order Mage_Sales_Model_Order
     * @return array
     */
    private function getGatewayParameters($order)
    {
        $helper = Mage::helper('paymentgateway');

        $url_success = Mage::helper('adminhtml')->getUrl('ipgpay/gateway/success');
        $url_decline = Mage::helper('adminhtml')->getUrl('ipgpay/gateway/cancel');
        $url_return = Mage::helper('adminhtml')->getUrl('ipgpay/gateway/return');

        $client_id = Mage::getStoreConfig('payment/paymentgateway/account_id');
        $test_transaction = Mage::getStoreConfig('payment/paymentgateway/test_mode') == '1' ? '1' : '0';

        $store = Mage::app()->getStore();

        $storeName = Mage::getStoreConfig('general/store_information/name');
        if (!$storeName) $storeName = $store->getName();
        $storeCountry = Mage::getStoreConfig('general/store_information/merchant_country');
        $formId = Mage::getStoreConfig('payment/paymentgateway/payment_form_id');

        return [
            'client_id' => $client_id,
            'return_url' => $url_return,
            'approval_url' => $url_success,
            'decline_url' => $url_decline,
            'test_transaction' => $test_transaction,
            'order_reference' => $order->getIncrementId(),
            'order_currency' => $order->getOrderCurrencyCode(),
            'form_id' => $formId,
            'merchant_name' => $storeName,
            'create_customer' => Mage::getStoreConfig('payment/paymentgateway/create_customers') == '1' ? '1' : '0'
        ];
    }

    /**
     * Get form fields for redirection to the payment form
     *
     * @param $customer
     * @param $orderItems
     * @param $gatewayInfo
     * @return array
     */
    private function mergeFormFields($customer, $orderItems, $gatewayInfo)
    {
        $fields = [];

        foreach ($gatewayInfo as $field => $value) {
            $fields[$field] = $value;
        }

        foreach ($customer as $field => $value) {
            $fields[$field] = $value;
        }

        foreach ($orderItems as $item) {
            foreach ($item as $field => $value) {
                $fields[$field] = $value;
            }
        }

        $signature_lifetime = Mage::getStoreConfig('payment/paymentgateway/request_expiry');
        if (!$signature_lifetime) $signature_lifetime = 24;
        $signature_type = 'PSSHA1';

        $fields['PS_EXPIRETIME'] = time() + 3600 * $signature_lifetime;
        $fields['PS_SIGTYPE'] = $signature_type;

        $signatureInfo = $this->createSignature($fields);
        $fields['PS_SIGNATURE'] = $signatureInfo;

        return $fields;
    }

    /**
     * Sign the request to the payment form
     *
     * @param $arr
     * @return string
     */
    private function createSignature($arr)
    {
        $secret = Mage::getStoreConfig("payment/paymentgateway/secret_key");
        $sigstring = $secret;

        ksort($arr, SORT_STRING);
        foreach ($arr as $key => $value) {
            $sigstring .= sprintf('&%s=%s', $key, $value);
        }

        return sha1($sigstring);
    }

}
