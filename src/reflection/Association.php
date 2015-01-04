<?php

namespace UniMapper\Reflection;

abstract class Association
{

    /** @var Entity */
    protected $sourceReflection;

    /** @var Entity */
    protected $targetReflection;

    /** @var bool */
    protected $dominant = true;

    /** @var array */
    protected $arguments = [];

    /** @var bool */
    protected $collection = true;

    /** @var string */
    protected $propertyName;

    public function __construct(
        $propertyName,
        Entity $sourceReflection,
        Entity $targetReflection,
        array $arguments,
        $dominant = true
    ) {
        $this->propertyName = $propertyName;
        $this->sourceReflection = $sourceReflection;
        $this->targetReflection = $targetReflection;
        $this->dominant = (bool) $dominant;
        $this->arguments = $arguments;

        if (!$this->sourceReflection->hasAdapter()) {
            throw new Exception\DefinitionException(
                "Can not use associations while source entity "
                . $sourceReflection->getName()
                . " has no adapter defined!"
            );
        }

        if (!$this->targetReflection->hasAdapter()) {
            throw new Exception\DefinitionException(
                "Can not use associations while target entity "
                . $targetReflection->getName() . " has no adapter defined!"
            );
        }
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
        return $this->sourceReflection->getPrimaryProperty()->getName(true);
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
        return $this->sourceReflection->getAdapterResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterName();
    }

    public function isRemote()
    {
        return $this->sourceReflection->getAdapterName()
            !== $this->targetReflection->getAdapterName();
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

}