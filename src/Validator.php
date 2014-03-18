<?php

namespace UniMapper;

class Validator
{

    public static function isTraversable($value)
    {
        return is_array($value) || is_object($value);
    }

}
