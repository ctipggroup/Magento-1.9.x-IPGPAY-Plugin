<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Gateway_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Additional initialization
     *
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Mage_Sales');
    }

    /**
     * Init layout, menu and breadcrumb
     *
     * @return Mage_Adminhtml_Sales_OrderController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/order')
            ->_addBreadcrumb($this->__('Sales'), $this->__('Sales'))
            ->_addBreadcrumb($this->__('Orders'), $this->__('Orders'));
        return $this;
    }

    /**
     * Initialize order model instance
     *
     * @return Mage_Sales_Model_Order || false
     */
    protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            $this->_getSession()->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }

    public function cancelorderrebillingAction()
    {
        //index.php/admin/order/cancelorderrebilling/
        if ($order = $this->_initOrder()) {
            try {
                $orderInfo = $order->getPayment()->getAdditionalData();
                if (empty($orderInfo)) {
                    $this->_getSession()->addError($this->__('Failed to cancel rebills, payment info could not be found.'));
                    $this->_redirect('*/sales_order/view', ['order_id' => $order->getId()]);
                    return;
                }
                $orderInfo = unserialize($orderInfo);
                if (!$orderInfo) {
                    $this->_getSession()->addError($this->__('Failed to cancel rebills, payment info could not be found.'));
                    $this->_redirect('*/sales_order/view', ['order_id' => $order->getId()]);
                    return;
                }

                $itemsToCancel = [];
                $hasRebills = false;
                foreach ($orderInfo as $Key => $Val) {
                    if (substr($Key, -6) == 'rebill' && $Val == 1) {
                        $hasRebills = true;

                        $parts = explode($Key, '_');
                        $id = $parts[1];
                        if (array_key_exists('item_' . $id . '_id', $orderInfo)) {
                            $itemsToCancel[$orderInfo['item_' . $id . '_id']] = [
                                'name' => 'item_' . $id . '_name',
                                'code' => 'item_' . $id . '_code',
                            ];
                        }
                    }
                }

                if (!$hasRebills) {
                    $this->_getSession()->addError($this->__('There are no rebill items on this order.'));
                    $this->_redirect('*/sales_order/view', ['order_id' => $order->getId()]);
                    return;
                }

                $client_id = Mage::getStoreConfig('payment/paymentgateway/account_id');
                $api_key = Mage::getStoreConfig('payment/paymentgateway/api_key');
                $test_mode = Mage::getStoreConfig('payment/paymentgateway/test_mode');
                $api_base_url = Mage::getStoreConfig('payment/paymentgateway/api_base_url');

                $cancel = new IPGPAY_Request_CancelRebill([
                    'api_base_url' => $api_base_url,
                    'api_client_id' => $client_id,
                    'api_key' => $api_key,
                    'notify' => '0',
                    'test_mode' => $test_mode
                ]);

                foreach ($itemsToCancel as $itemId => $itemInfo) {
                    $cancel->setItemId($itemId);
                    try {
                        $res = $cancel->sendRequest();
                        $res['result'] = $res;
                    } catch (Exception $e) {
                        $this->_getSession()->addError($this->__('Communications error with gateway. Please try again.'));
                        $this->_redirect('*/sales_order/view', ['order_id' => $order->getId()]);
                        return;
                    }
                }

                $hasFailures = false;
                $str = '';
                foreach ($itemsToCancel as $itemId => $itemInfo) {
                    if ($itemInfo['result'] instanceof IPGPAY_Response_Success) {
                        //Purposefully left blank
                    } else {
                        $hasFailures = true;
                    }

                    $str .= 'Rebill ' . htmlspecialchars($itemId) . ': ' .
                        htmlspecialchars($itemInfo['name']) .
                        (empty($itemInfo['code']) ? '' : htmlspecialchars(empty($itemInfo['code']))) . ' - ' .
                        $itemInfo['result']->Response . ' (' . $itemInfo['result']->ResponseCode . ') ' . $itemInfo['result']->ResponseText . '<br \>';
                }

                if ($hasFailures) {
                    $msg = 'Some rebills could not be cancelled. Log into the gateway for further details.' . '<br \>';
                } else {
                    $msg = 'All rebills cancelled successfully' . '<br \>';
                }

                $msg .= $str;
                $order->setState($order->getState(), true, $msg, false);
                $this->_getSession()->addSuccess($this->__($msg));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Failed to cancel rebills. Please try again.'));
                Mage::logException($e);
            }
        }
        $this->_redirect('*/sales_order/view', ['order_id' => $order->getId()]);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/config/payment');
    }

}