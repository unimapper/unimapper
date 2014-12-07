<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class OneToMany extends Reflection\Association
{

    public function __construct(
        Reflection\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        array $arguments
    ) {
        if (!isset($arguments[0])) {
            throw new Exception\DefinitionException(
                "You must define a foreign key!"
            );
        }

        parent::__construct($propertyReflection, $targetReflection, $arguments, true);
    }

    public function getForeignKey()
    {
        return $this->arguments[0];
    }

}