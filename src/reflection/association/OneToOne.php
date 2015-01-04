<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Exception;
use UniMapper\Reflection;

class OneToOne extends OneToMany
{

    /** @var bool */
    protected $collection = false;

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