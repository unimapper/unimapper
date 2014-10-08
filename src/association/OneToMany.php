<?php

namespace UniMapper\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class OneToMany extends Multi
{

    protected $expression = "1:N\s*=\s*(.*)";

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (empty($this->matches[1])) {
            throw new Exception\DefinitionException(
                "You must define foreign key name '". $definition . "'!"
            );
        }
    }

    public function getForeignKey()
    {
        return $this->matches[1];
    }

}