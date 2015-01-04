<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class OneToMany extends Reflection\Association
{

    public function __construct(
        $propertyName,
        Reflection\Entity $sourceReflection,
        Reflection\Entity $targetReflection,
        array $arguments
    ) {
        parent::__construct(
            $propertyName,
            $sourceReflection,
            $targetReflection,
            $arguments
        );

        if (!isset($arguments[0])) {
            throw new Exception\DefinitionException(
                "You must define a foreign key!"
            );
        }
    }

    public function getForeignKey()
    {
        return $this->arguments[0];
    }

}