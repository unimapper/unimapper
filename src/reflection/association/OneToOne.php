<?php

namespace UniMapper\Reflection\Association;

use UniMapper\Reflection;

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
    }

    public function getKey()
    {
        return $this->getForeignKey();
    }

}