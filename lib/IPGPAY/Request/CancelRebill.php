<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Request_CancelRebill extends IPGPAY_Request_Abstract
{
    protected $OrderId; //Mandatory
    protected $Reason; //Optional
    protected $ItemId; //Mandatory

    /**
     * Set the Order Id
     *
     * @param $OrderId
     * @throws IPGPAY_InvalidRequestException
     */
    public function setOrderId($OrderId)
    {
        if (!IPGPAY_Functions::isValidSqlInt($OrderId)) {
            throw new IPGPAY_InvalidRequestException("Invalid Order Id");
        }
        $this->OrderId = $OrderId;
    }

    public function setItemId($ItemId)
    {
        if (!IPGPAY_Functions::isValidSqlInt($ItemId)) {
            throw new IPGPAY_InvalidRequestException("Invalid Item Id");
        }
        $this->ItemId = $ItemId;
    }

    /**
     * Set the reason for the void
     * Truncate to 100 chars
     *
     * @param $Reason
     */
    public function setReason($Reason)
    {
        if (strlen($Reason) > 100) {
            $Reason = substr($Reason, 0, 100);
        }
        $this->Reason = $Reason;
    }

    /**
     * Validate the void request parameters
     *
     * @throws IPGPAY_InvalidRequestException
     */
    protected function validate()
    {
        parent::validate();
        if (empty($this->OrderId)) {
            throw new IPGPAY_InvalidRequestException("Missing Order Id");
        }
    }

    /**
     * Build the request params
     *
     * @return array
     */
    protected function buildRequestParams()
    {
        $Request = [];
        $Request['client_id'] = $this->APIClientId;
        $Request['api_key'] = $this->APIKey;
        $Request['order_id'] = $this->OrderId;
        if (!empty($this->Reason)) {
            $Request['reason'] = $this->Reason;
        }
        $Request['notify'] = $this->Notify;
        $Request['item_id'] = $this->ItemId;
        $this->RequestParams = $Request;
        return $Request;
    }

    /**
     * Return the request URL for the void request
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->APIBaseUrl . '/service/order/void';
    }
}