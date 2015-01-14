<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

abstract class Selectable extends Conditionable
{

    /** @var array */
    protected $associations = [
        "local" => [],
        "remote" => []
    ];

    public function associate($propertyName)
    {
        foreach (func_get_args() as $name) {

            if (!$this->entityReflection->hasProperty($name)) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' not defined!"
                );
            }

            $property = $this->entityReflection->getProperty($name);
            if (!$property->hasOption(Reflection\Property::OPTION_ASSOC)) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' is not defined as association!"
                );
            }

            $association = $property->getOption(Reflection\Property::OPTION_ASSOC);
            if ($association->isRemote()) {
                $this->associations["remote"][$name] = $association;
            } else {
                $this->associations["local"][$name] = $association;
            }
        }

        return $this;
    }

}