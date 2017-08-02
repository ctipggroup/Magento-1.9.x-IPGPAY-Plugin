<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
abstract class IPGPAY_Response_Abstract
{
    const RESPONSE_APPROVED = 'A';
    const RESPONSE_DECLINED = 'D';
    const RESPONSE_ERROR = 'E';

    public $Response, $ResponseCode, $ResponseText, $TransId;

    public $ResponseString = '';

    /**
     * Based on a response string, construct and return the relevant response object
     *
     * @param $ResponseString
     * @return IPGPAY_Response_Declined|IPGPAY_Response_Error|IPGPAY_Response_Success
     * @throws IPGPAY_InvalidResponseException
     */
    public static function factory($ResponseString)
    {
        try {
            $Xml = new SimpleXMLElement($ResponseString);
        } catch (Exception $e) {
            throw new IPGPAY_InvalidResponseException("Invalid response: $ResponseString, reason: " . $e->getMessage());
        }

        if (isset($Xml->response)) {
            switch ($Xml->response) {
                case self::RESPONSE_APPROVED:
                    return new IPGPAY_Response_Success($Xml);
                    break;
                case self::RESPONSE_DECLINED:
                    return new IPGPAY_Response_Declined($Xml);
                    break;
                case self::RESPONSE_ERROR:
                default:
                    return new IPGPAY_Response_Error($Xml);
                    break;
            }
        } else {
            return new IPGPAY_Response_Error($Xml);
        }
    }

    /**
     * @param SimpleXMLElement $Xml
     */
    function __construct(SimpleXMLElement $Xml)
    {
        if (isset($Xml->response)) {
            $this->Response = (string)$Xml->response;
        }
        if (isset($Xml->responsecode)) {
            $this->ResponseCode = (string)$Xml->responsecode;
        }
        if (isset($Xml->responsecode)) {
            $this->ResponseText = (string)$Xml->responsetext;
        }
        if (isset($Xml->trans_id)) {
            $this->TransId = (string)$Xml->trans_id;
        }
    }
}