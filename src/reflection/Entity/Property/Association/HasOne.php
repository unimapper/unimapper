<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class HasOne extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "N:1";

    public function __construct(Reflection\Entity $currentEntityReflection, Reflection\Entity $targetEntityReflection, $parameters)
    {
        parent::__construct($currentEntityReflection, $targetEntityReflection, $parameters);
        if (!isset($this->parameters[0])) {
            throw new \Exception("You must define a reference key!");
        }
    }

    public function getReferenceKey()
    {
        return $this->parameters[0];
    }

}