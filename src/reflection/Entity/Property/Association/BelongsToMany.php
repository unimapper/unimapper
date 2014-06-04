<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class BelongsToMany extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "1:N";

    public function __construct(\UniMapper\Reflection\Entity $currentEntityReflection, \UniMapper\Reflection\Entity $targetEntityReflection, $parameters)
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