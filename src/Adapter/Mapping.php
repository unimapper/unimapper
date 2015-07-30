<?php

namespace UniMapper\Adapter;

use UniMapper\Entity\Reflection\Property;

abstract class Mapping
{

    public function mapValue(Property $property, $value)
    {
        return $value;
    }

    public function unmapValue(Property $property, $value)
    {
        return $value;
    }

}