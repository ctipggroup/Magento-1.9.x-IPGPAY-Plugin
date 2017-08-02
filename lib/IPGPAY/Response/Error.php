<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Response_Error extends IPGPAY_Response_Abstract
{
    public $Errors = [];

    /**
     * Construct the error response
     * Set the response code and response text to that of the first error
     * Keep a list of the full errors
     *
     * @param SimpleXMLElement $Xml
     */
    function __construct(SimpleXMLElement $Xml)
    {
        parent::__construct($Xml);
        if (!isset($Xml->response) && isset($Xml->errors)) {
            $this->Response = self::RESPONSE_ERROR;
            foreach ($Xml->errors as $error) {
                if (empty($this->ResponseCode)) {
                    $this->ResponseCode = (string)$error->error->code;
                    $this->ResponseText = (string)$error->error->text;
                }
                $this->Errors[(string)$error->error->code] = (string)$error->error->text;
            }
        }
    }
}