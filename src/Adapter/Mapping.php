<?php

namespace UniMapper\Adapter;

use UniMapper\Reflection;

abstract class Mapping
{

    public function mapValue(Reflection\Property $property, $value)
    {
        return $value;
    }

    public function unmapValue(Reflection\Property $property, $value)
    {
        return $value;
    }

}