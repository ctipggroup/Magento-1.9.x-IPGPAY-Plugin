<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Request_Credit extends IPGPAY_Request_Abstract
{
    protected $OrderId; //Mandatory
    protected $TransId; //Mandatory
    protected $Amount; //Optional
    protected $Reason; //Optional
    protected $Reference; //Optional

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
     * Set the Trams Id
     *
     * @param $TransId
     * @throws IPGPAY_InvalidRequestException
     */
    public function setTransId($TransId)
    {
        if (!IPGPAY_Functions::isValidSqlBigInt($TransId)) {
            throw new IPGPAY_InvalidRequestException("Invalid Trans Id");
        }
        $this->TransId = $TransId;
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
            throw new IPGPAY_InvalidRequestException("Invalid Credit Amount");
        }
        $this->Amount = $Amount;
    }

    /**
     * Set the reason for the void
     *
     * @param $Reason
     */
    public function setReason($Reason)
    {
        $this->Reason = $Reason;
    }

    /**
     * Set the reason for the void
     * Truncate to 100 chars
     *
     * @param $Reference
     */
    public function setReference($Reference)
    {
        if (strlen($Reference) > 100) {
            $Reference = substr($Reference, 0, 100);
        }
        $this->Reference = $Reference;
    }

    /**
     * Validate the credit request parameters
     *
     * @throws IPGPAY_InvalidRequestException
     */
    protected function validate()
    {
        parent::validate();
        if (empty($this->OrderId)) {
            throw new IPGPAY_InvalidRequestException("Missing Order Id");
        }
        if (empty($this->TransId)) {
            throw new IPGPAY_InvalidRequestException("Missing Trans Id");
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
        $Request['trans_id'] = $this->TransId;
        if (!empty($this->Amount)) {
            $Request['amount'] = $this->Amount;
        }
        if (!empty($this->Reason)) {
            $Request['reason'] = $this->Reason;
        }
        if (!empty($this->Reference)) {
            $Request['reference'] = $this->Reference;
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
     * Return the request URL for the credit request
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->APIBaseUrl . '/service/order/credit';
    }
}