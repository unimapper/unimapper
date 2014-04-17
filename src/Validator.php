<?php

namespace UniMapper;

class Validator
{

    public static function isTraversable($value)
    {
        return is_array($value) || is_object($value);
    }

    public static function isUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public static function isEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function isIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    public static function isIpv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function isIpv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

}