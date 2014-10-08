<?php

namespace UniMapper\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class ManyToOne extends Single
{

    protected $expression = "N:1\s*=\s*(.*)";

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (empty($this->matches[1])) {
            throw new Exception\PropertyDefinitionException(
                "You must define a reference key!"
            );
        }
    }

    public function getReferenceKey()
    {
        return $this->matches[1];
    }

}