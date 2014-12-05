<?php

namespace UniMapper\Adapter;

use UniMapper\Reflection;

abstract class Mapping
{

    public function mapValue(Reflection\Entity\Property $property, $value)
    {
        return $value;
    }

    public function unmapValue(Reflection\Entity\Property $property, $value)
    {
        return $value;
    }

}