<?php

namespace UniMapper\Query;

use UniMapper\Exception;

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

            if (!isset($this->entityReflection->getProperties()[$name])) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' not defined!"
                );
            }

            $property = $this->entityReflection->getProperties()[$name];
            if (!$property->isAssociation()) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' is not defined as association!"
                );
            }

            $association = $property->getAssociation();
            if ($association->isRemote()) {
                $this->associations["remote"][$name] = $association;
            } else {
                $this->associations["local"][$name] = $association;
            }
        }

        return $this;
    }

}