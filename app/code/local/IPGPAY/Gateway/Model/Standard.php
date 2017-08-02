<?php
/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */

require_once Mage::getBaseDir('lib') . DS . 'IPGPAY' . DS . 'IPGPAY.php';

class IPGPAY_Gateway_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    /*new added payment method code define here*/
    protected $_code = 'paymentgateway';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canCreateBillingAgreement = false;
    protected $_canManageRecurringProfiles = true;
    protected $_canFetchTransactionInfo = false;

    /*set redirect url after order place */
    public function getOrderPlaceRedirectUrl()
    {
        $url = Mage::getUrl("ipgpay/gateway/redirect");
        return $url;
    }


    /**
     * capture - Settle an authorization transaction
     *
     * @param Varien_Object $payment
     * @param int|float $amount
     * @return Mage_Payment_Model_Method_Cc $this.
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if ($this->getDebug()) {
            $writer = new Zend_Log_Writer_Stream($this->getLogPath());
            $logger = new Zend_Log($writer);
        }

        $orderExtraInfo = $payment->getAdditionalData();

        if (empty($orderExtraInfo)) {
            if ($this->getDebug()) {
                $logger->info("Unable to locate original order reference");
            }
            Mage::throwException("Unable to locate original order reference");
        }

        $client_id = Mage::getStoreConfig('payment/paymentgateway/account_id');
        $api_key = Mage::getStoreConfig('payment/paymentgateway/api_key');
        $test_mode = Mage::getStoreConfig('payment/paymentgateway/test_mode');
        $api_base_url = Mage::getStoreConfig('payment/paymentgateway/api_base_url');

        $Capture = new IPGPAY_Request_Settle([
            'api_base_url' => $api_base_url,
            'api_client_id' => $client_id,
            'api_key' => $api_key,
            'notify' => '1',
            'test_mode' => $test_mode
        ]);

        $orderExtraInfo = unserialize($orderExtraInfo);
        try {
            $Capture->setOrderId($orderExtraInfo['order_id']);
            $res = $Capture->sendRequest();


            if ($res instanceof IPGPAY_Response_Success) {
                $payment->setCcTransId($res->TransId);
                $payment->setTransactionId($res->TransId);
            } else {
                Mage::throwException($res->Response . ' (' . $res->ResponseCode . ') ' . $res->ResponseText);
            }
        } catch (Exception $e) {
            Mage::throwException("Cannot issue a capture on this transaction: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * void - Cancel an authorization transaction that has not yet been settled.
     *
     * @param Varien_Object $payment
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function void(Varien_Object $payment)
    {
        if ($this->getDebug()) {
            $writer = new Zend_Log_Writer_Stream($this->getLogPath());
            $logger = new Zend_Log($writer);
        }

        $orderExtraInfo = $payment->getAdditionalData();

        if (empty($orderExtraInfo)) {
            if ($this->getDebug()) {
                $logger->info("Unable to locate original order reference");
            }
            Mage::throwException("Unable to locate original order reference");
        }

        $client_id = Mage::getStoreConfig('payment/paymentgateway/account_id');
        $api_key = Mage::getStoreConfig('payment/paymentgateway/api_key');
        $test_mode = Mage::getStoreConfig('payment/paymentgateway/test_mode');
        $api_base_url = Mage::getStoreConfig('payment/paymentgateway/api_base_url');

        $Void = new IPGPAY_Request_Void([
            'api_base_url' => $api_base_url,
            'api_client_id' => $client_id,
            'api_key' => $api_key,
            'notify' => '1',
            'test_mode' => $test_mode
        ]);

        $orderExtraInfo = unserialize($orderExtraInfo);

        try {
            $Void->setOrderId($orderExtraInfo['order_id']);
            $res = $Void->sendRequest();
            if ($res instanceof IPGPAY_Response_Success) {
                $payment->setTransactionId($res->TransId);
            } else {
                Mage::throwException($res->Response . ' (' . $res->ResponseCode . ') ' . $res->ResponseText);
            }
        } catch (Exception $e) {
            Mage::throwException("Cannot issue a void on this transaction: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * refund - Processes a partial or whole refund on an existing transaction.
     *
     * @param Varien_Object $payment
     * @param int|float $amount
     * @return Mage_Payment_Model_Method_Cc $this.
     * @throws Mage_Core_Exception
     */
    public function refund(Varien_Object $payment, $amount)
    {
        if ($this->getDebug()) {
            $writer = new Zend_Log_Writer_Stream($this->getLogPath());
            $logger = new Zend_Log($writer);
        }

        $orderExtraInfo = $payment->getAdditionalData();

        if (empty($orderExtraInfo)) {
            if ($this->getDebug()) {
                $logger->info("Unable to locate original order reference");
            }
            Mage::throwException("Unable to locate original order reference");
        }

        $client_id = Mage::getStoreConfig('payment/paymentgateway/account_id');
        $api_key = Mage::getStoreConfig('payment/paymentgateway/api_key');
        $test_mode = Mage::getStoreConfig('payment/paymentgateway/test_mode');
        $api_base_url = Mage::getStoreConfig('payment/paymentgateway/api_base_url');

        $Credit = new IPGPAY_Request_Credit([
            'api_base_url' => $api_base_url,
            'api_client_id' => $client_id,
            'api_key' => $api_key,
            'notify' => '1',
            'test_mode' => $test_mode
        ]);

        $orderExtraInfo = unserialize($orderExtraInfo);

        try {
            $Credit->setOrderId($orderExtraInfo['order_id']);
            $Credit->setTransId($payment->getCcTransId());
            $Credit->setAmount($amount);
            $res = $Credit->sendRequest();

            if ($res instanceof IPGPAY_Response_Success) {
                $payment->setTransactionId($res->TransId);
            } else {
                Mage::throwException($res->Response . ' (' . $res->ResponseCode . ') ' . $res->ResponseText);
            }
        } catch (Exception $e) {
            Mage::throwException("Cannot issue a credit on this transaction: " . $e->getMessage());
        }

        return $this;
    }
}
