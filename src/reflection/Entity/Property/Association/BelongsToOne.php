<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class BelongsToOne extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "1:1";

    public function __construct(Reflection\Entity $currentEntityReflection, Reflection\Entity $targetEntityReflection, $parameters)
    {
        parent::__construct($currentEntityReflection, $targetEntityReflection, $parameters);
        if (!isset($this->parameters[0])) {
            throw new \Exception("You must define foreign key!");
        }
    }

    public function getForeignKey()
    {
        return $this->parameters[0];
    }

}