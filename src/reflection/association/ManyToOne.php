<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class ManyToOne extends \UniMapper\Reflection\Association
{

    /** @var bool */
    protected $collection = false;

    public function __construct(
        Reflection\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        array $arguments
    ) {
        if (!isset($arguments[0])) {
            throw new Exception\DefinitionException(
                "You must define a reference key!"
            );
        }

        if (!$targetReflection->hasPrimary()) {
            throw new Exception\DefinitionException(
                "Target entity must have defined primary when N:1 relation used!"
            );
        }

        parent::__construct($propertyReflection, $targetReflection, $arguments, true);
    }

    public function getKey()
    {
        return $this->getReferenceKey();
    }

    public function getReferenceKey()
    {
        return $this->arguments[0];
    }

    public function getTargetPrimaryKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

}