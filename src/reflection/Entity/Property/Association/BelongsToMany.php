<?php

namespace UniMapper\Reflection\Entity\Property\Association;

use UniMapper\Reflection;

class BelongsToMany extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "1:N";

    public function __construct(Reflection\Entity $currentReflection,
        Reflection\Entity $targetReflection, $parameters
    ) {
        parent::__construct($currentReflection, $targetReflection, $parameters);
        if (!isset($this->parameters[0])) {
            throw new \Exception("You must define foreign key!");
        }
    }

    public function getForeignKey()
    {
        return $this->parameters[0];
    }

}