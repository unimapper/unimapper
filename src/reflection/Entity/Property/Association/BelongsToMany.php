<?php

namespace UniMapper\Reflection\Entity\Property\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class BelongsToMany extends Reflection\Entity\Property\Association
{

    protected $expression = "1:N\s*=\s*(.*)";

    public function __construct(
        Reflection\Entity $currentReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($currentReflection, $targetReflection, $definition);

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