<?php

namespace UniMapper\Reflection;

use UniMapper\Exception;

abstract class Association
{

    /** @var Property */
    protected $propertyReflection;

    /** @var Entity */
    protected $targetReflection;

    /** @var bool */
    protected $dominant = true;

    /** @var array */
    protected $arguments = [];

    /** @var bool */
    protected $collection = true;

    public function __construct(
        Property $propertyReflection,
        Entity $targetReflection,
        array $arguments,
        $dominant = true
    ) {
        $this->propertyReflection = $propertyReflection;
        $this->targetReflection = $targetReflection;
        $this->dominant = (bool) $dominant;
        $this->arguments = $arguments;
    }

    /**
     * Is target result entity collection
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool) $this->collection;
    }

    public function getPrimaryKey()
    {
        return $this->propertyReflection->getEntityReflection()
            ->getPrimaryProperty()
            ->getName(true);
    }

    public function getKey()
    {
        return $this->getPrimaryKey();
    }

    public function getTargetReflection()
    {
        return $this->targetReflection;
    }

    public function getTargetResource()
    {
        return $this->targetReflection->getAdapterResource();
    }

    public function getSourceResource()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterName();
    }

    public function isRemote()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterName()
            !== $this->targetReflection->getAdapterName();
    }

    public function getPropertyName()
    {
        return $this->propertyReflection->getName();
    }

}