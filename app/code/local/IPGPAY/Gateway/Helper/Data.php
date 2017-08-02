<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Gateway_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getPaymentmethodPaymentgatewayurl()
    {
        return preg_replace('#/payment/form/post#i', '', Mage::getstoreConfig('payment/paymentgateway/payment_form_url')) . '/payment/form/post';
    }
}
