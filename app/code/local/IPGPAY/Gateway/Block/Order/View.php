<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Gateway_Block_Order_View extends Mage_Adminhtml_Block_Sales_Order_View
{
    public function __construct()
    {
        parent::__construct();

        $orderInfo = $this->getOrder()->getPayment()->getAdditionalData();
        if (empty($orderInfo)) return;
        $orderInfo = unserialize($orderInfo);
        if (!$orderInfo) return;

        $hasRebills = false;
        foreach ($orderInfo as $Key => $Val) {
            if (substr($Key, -6) == 'rebill' && $Val == 1) {
                $hasRebills = true;
                break;
            }
        }

        if (!$hasRebills) {
            return;
        }

        $coreHelper = Mage::helper('core');
        $confirmationMessage = $coreHelper->jsQuoteEscape(
            Mage::helper('sales')->__('Are you sure you want to cancel all of the rebills on this order?')
        );
        $this->_addButton('cancel_rebiling', [
            'label' => Mage::helper('sales')->__('Cancel Rebilling'),
            'onclick' => "confirmSetLocation('{$confirmationMessage}', '{$this->getOrderRebillCancelURL($orderInfo['order_id'])}')",
        ]);
    }

    protected function getOrderRebillCancelURL($order_id)
    {
        return $this->getUrl('*/order/cancelorderrebilling', ['order_id' => $order_id]);
    }
}