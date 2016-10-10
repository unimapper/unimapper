<?php

namespace UniMapper\Adapter;

use UniMapper\Entity\Reflection;
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
    
    public function unmapFilterJoins(Reflection $reflection, array $filter)
    {
        return [];
    }
    
    public function unmapFilterJoinProperty(Reflection $reflection, $name)
    {
        return $name;
    }

}