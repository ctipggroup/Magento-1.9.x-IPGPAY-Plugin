<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
abstract class IPGPAY_Request_Abstract
{
    protected $APIBaseUrl, $APIClientId, $APIKey;
    protected $TestMode = '0', $Notify = '1';
    protected $RequestParams = [];

    /**
     * Build the request params
     * To be implemented by the extended classes
     *
     * @return mixed
     */
    abstract protected function buildRequestParams();

    /**
     * Get the IPGPAY for the request
     *
     * @return mixed
     */
    abstract protected function getRequestUrl();

    /**
     * @param array $Config
     */
    function __construct(array $Config = [])
    {
        if (empty($Config)) {
            $this->APIBaseUrl = IPGPAY_Config::API_BASE_URL;
            $this->APIClientId = IPGPAY_Config::API_CLIENT_ID;
            $this->APIKey = IPGPAY_Config::API_KEY;
            $this->Notify = IPGPAY_Config::NOTIFY;
            $this->TestMode = IPGPAY_Config::TEST_MODE;
        } else {
            $this->APIBaseUrl = $Config['api_base_url'];
            $this->APIClientId = $Config['api_client_id'];
            $this->APIKey = $Config['api_key'];
            $this->Notify = $Config['notify'];
            $this->TestMode = $Config['test_mode'];
        }
    }


    /**
     * Validate the request parameters
     *
     * @throws IPGPAY_InvalidRequestException
     */
    protected function validate()
    {
        if (empty($this->APIBaseUrl)) {
            throw new IPGPAY_InvalidRequestException("API URL is missing.");
        } elseif (filter_var($this->APIBaseUrl, FILTER_VALIDATE_URL) === false) {
            throw new IPGPAY_InvalidRequestException("API URL is invalid.");
        }

        if (empty($this->APIClientId)) {
            throw new IPGPAY_InvalidRequestException("API Client Id is missing.");
        } else {
            if (!IPGPAY_Functions::isValidSqlInt($this->APIClientId)) {
                throw new IPGPAY_InvalidRequestException("Invalid API Client Id");
            }
        }

        if (empty($this->APIKey)) {
            throw new IPGPAY_InvalidRequestException("API Key is missing.");
        }
    }

    /**
     * Validate, build request params and send to IPGPAY
     * Return a response
     *
     * @return IPGPAY_Response_Abstract
     * @throws IPGPAY_CommunicationException
     */
    public function sendRequest()
    {
        $this->validate();
        $this->buildRequestParams();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getRequestUrl());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->RequestParams));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $Response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new IPGPAY_CommunicationException("cURL error: " . curl_errno($ch) . " " . curl_error($ch));
        }

        curl_close($ch);
        return IPGPAY_Response_Abstract::factory($Response);
    }
}