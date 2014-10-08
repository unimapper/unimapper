<?php

namespace UniMapper\Association;

use UniMapper\Entity;

abstract class Multi extends \UniMapper\Association
{

    /** @var array */
    private $attached = [];

    /** @var array */
    private $detached = [];

    /** @var array */
    private $added = [];

    /** @var array */
    private $removed = [];

    public function attach(Entity $entity)
    {
        $this->_manipulate($entity, "attached");
    }

    public function detach(Entity $entity)
    {
        $this->_manipulate($entity, "detached");
    }

    public function add(Entity $entity)
    {
        $this->validateEntity($entity);
        $this->added[] = $entity;
    }

    public function remove(Entity $entity)
    {
        $this->validateEntity($entity, true);
        $this->removed[] = $entity;
    }

    public function getAttached()
    {
        return $this->attached;
    }

    public function getDetached()
    {
        return $this->detached;
    }

    public function getAdded()
    {
        return $this->added;
    }

    public function getRemoved()
    {
        return $this->removed;
    }

    private function _manipulate($entity, $action)
    {
        $this->validateEntity($entity, true);

        $primary = $entity->{$entity->getReflection()->getPrimaryProperty()->getName()};

        if (!in_array($primary, $this->{$action}, true)) {
            array_push($this->{$action}, $primary);
        }
    }

}