<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Exception\OptionException;

class Primary implements Reflection\Property\IOption
{

    const KEY = "primary";

    public static function create(
        Reflection\Property $property,
        $value = null,
        array $parameters = []
    ) {
        if ($property->getEntityReflection()->hasPrimary()) {
            throw new OptionException("Primary property already defined!");
        }

        // Validate primary type
        $requiredType = [
            Reflection\Property::TYPE_DOUBLE,
            Reflection\Property::TYPE_INTEGER,
            Reflection\Property::TYPE_STRING
        ];
        if (!in_array($property->getType(), $requiredType, true)) {
            throw new OptionException(
                "Primary property can be only " . implode(",", $requiredType)
                . " but '" . $property->getType() . "' given!"
            );
        }

        return new self;
    }

    /**
     * Checks if primary value is empty
     *
     * @param $value
     *
     * @return bool
     */
    public static function isEmpty($value)
    {
        return $value === "" || $value === null ? true : false;
    }

}