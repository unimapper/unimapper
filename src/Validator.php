<?php

namespace UniMapper;

trait Validator
{

    public static function validateTraversable($value)
    {
        return is_array($value) || is_object($value);
    }

    public static function validateUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public static function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function validateIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    public static function validateIpv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function validateIpv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

}