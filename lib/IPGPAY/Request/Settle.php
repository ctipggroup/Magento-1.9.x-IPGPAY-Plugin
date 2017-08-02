<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Request_Settle extends IPGPAY_Request_Abstract
{
    protected $OrderId; //Mandatory
    protected $ShipperId; //Optional
    protected $TrackId; //TrackId
    protected $Amount; //Optional

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

    /**
     * Set the Shipper Id
     * Truncate to 40 chars
     *
     * @param $ShipperId
     */
    public function setShipperId($ShipperId)
    {
        if (strlen($ShipperId) > 40) {
            $ShipperId = substr($ShipperId, 0, 40);
        }
        $this->ShipperId = $ShipperId;
    }

    /**
     * Set the Order Id
     * Truncate to 40 chars
     *
     * @param $TrackId
     */
    public function setTrackId($TrackId)
    {
        if (strlen($TrackId) > 40) {
            $TrackId = substr($TrackId, 0, 40);
        }
        $this->TrackId = $TrackId;
    }

    /**
     * Set the amount
     *
     * @param $Amount
     * @throws IPGPAY_InvalidRequestException
     */
    public function setAmount($Amount)
    {
        if (!IPGPAY_Functions::isValidAmount($Amount)) {
            throw new IPGPAY_InvalidRequestException("Invalid Settle Amount");
        }
        $this->Amount = $Amount;
    }

    /**
     * Validate the settle request parameters
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
        if (!empty($this->Amount)) {
            $Request['amount'] = $this->Amount;
        }
        if (!empty($this->ShipperId)) {
            $Request['shipper_id'] = $this->ShipperId;
        }
        if (!empty($this->TrackId)) {
            $Request['track_id'] = $this->TrackId;
        }
        $Request['notify'] = $this->Notify;
        if ($this->TestMode) {
            $Request['test_transaction'] = '1';
        } else {
            $Request['test_transaction'] = '0';
        }
        $this->RequestParams = $Request;
        return $Request;
    }

    /**
     * Return the request URL for the settlement request
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->APIBaseUrl . '/service/order/settle';
    }
}