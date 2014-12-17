<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class OneToOne extends OneToMany
{

    /** @var bool */
    protected $collection = false;

    public function __construct(
        Reflection\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        array $arguments
    ) {
        parent::__construct($propertyReflection, $targetReflection, $arguments, true);

        if (!$targetReflection->hasPrimary()) {
            throw new Exception\DefinitionException(
                "Target entity must have defined primary when 1:1 relation used!"
            );
        }
    }

    public function getKey()
    {
        return $this->getForeignKey();
    }

    public function getTargetPrimaryKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

}