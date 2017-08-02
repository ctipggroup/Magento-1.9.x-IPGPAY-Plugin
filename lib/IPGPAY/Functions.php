<?php

/**
 * @copyright Copyright (c) 2002 - 2017 IPG Group Limited (a company incorporated in the SAR of Hong Kong).
 * All rights reserved. Use is strictly subject to licence terms & conditions.
 * This computer software programme is protected by copyright law and international treaties.
 * Unauthorised reproduction, reverse engineering or distribution of the programme, or any part of it, may
 * result in severe civil and criminal penalties and will be prosecuted to the maximum extent permissible at law.
 * for further information, please contact the copyright owner by email copyright@ipggroup.com
 */
class IPGPAY_Functions
{
    /**
     * Valid numeric amount. Checks to how many digits the decimal amount has if decimals exists. At most it can be 2.
     *
     * @param $Amount
     * @return bool
     */
    public static function isValidAmount($Amount)
    {
        if (!is_numeric($Amount)) {
            return false;
        }
        if (strstr($Amount, '.')) {
            $dot = strrpos($Amount, '.');
            if ($dot == 0) {
                $centlen = strlen($Amount) - 1;
                if ($centlen > 2) return false;
            } else {
                if ($dot > 0) {
                    $centlen = strlen($Amount) - strrpos($Amount, '.') - 1;
                    if ($centlen > 2) return false;
                }
            }
        }
        return true;
    }

    /**
     * Validate Sql int
     *
     * @param $value
     * @return bool
     */
    public static function isValidSqlInt($value)
    {
        if (preg_match('/^\d+$/', (string)$value) && $value <= 2147483647) {
            return true;
        }

        return false;
    }

    /**
     * Validate Sql smallint
     *
     * @param $value
     * @return bool
     */
    public static function isValidSqlSmallInt($value)
    {
        if (preg_match('/^\d+$/', (string)$value) && $value <= 32767) {
            return true;
        }

        return false;
    }

    /**
     * Validate Sql bigint
     *
     * @param $value
     * @return bool
     */
    public static function isValidSqlBigInt($value)
    {
        if (preg_match('/^\d+$/', (string)$value) && $value <= 9223372036854775807) {
            return true;
        }

        return false;
    }
}