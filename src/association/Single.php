<?php

namespace UniMapper\Association;

use UniMapper\Entity;

abstract class Single extends \UniMapper\Association
{

    private $attached;

    public function attach(Entity $entity)
    {
        $this->validateEntity($entity, true);
        $this->attached = $entity->{$entity->getReflection()->getPrimaryProperty()->getName()};
    }

    public function getAttached()
    {
        return $this->attached;
    }

}