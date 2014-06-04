<?php

namespace UniMapper\Reflection\Entity\Property\Association;

use UniMapper\Reflection;

class HasMany extends \UniMapper\Reflection\Entity\Property\Association
{

    const TYPE = "M:N";

    public function __construct(Reflection\Entity $currentEntityReflection, Reflection\Entity $targetEntityReflection, $parameters)
    {
        parent::__construct($currentEntityReflection, $targetEntityReflection, $parameters);
        if (!$targetEntityReflection->hasPrimaryProperty()) {
            throw new \Exception("Target entity must define primary property!");
        }
        if (!isset($this->parameters[0])) {
            throw new \Exception("You must define join key!");
        }
        if (!isset($this->parameters[1])) {
            throw new \Exception("You must define join resource!");
        }
        if (!isset($this->parameters[2])) {
            throw new \Exception("You must define reference key!");
        }
    }

    public function getJoinKey()
    {
        return $this->parameters[0];
    }

    public function getJoinResource()
    {
        return $this->parameters[1];
    }

    public function getReferenceKey()
    {
        return $this->parameters[2];
    }

    public function getForeignKey()
    {
        return $this->targetEntityReflection->getPrimaryProperty()->getMappedName();
    }

}