<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class HasOne extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "N:1";

    public function __construct(Reflection\Entity $currentReflection,
        Reflection\Entity $targetReflection, $parameters
    ) {
        parent::__construct($currentReflection, $targetReflection, $parameters);
        if (!isset($this->parameters[0])) {
            throw new \Exception("You must define a reference key!");
        }
    }

    public function getReferenceKey()
    {
        return $this->parameters[0];
    }

}