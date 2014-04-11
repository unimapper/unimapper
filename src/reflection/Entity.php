<?php

namespace UniMapper\Reflection;

use UniMapper\Exceptions\PropertyException;

/**
 * Entity reflection
 */
class Entity
{

    /** @var array */
    private $mappers;

    /** @var array */
    private $properties;

    /** @var string */
    private $className;

    /** @var string */
    private $parentClassName;

    /** @var string */
    private $fileName;

    /** @var string */
    private $docComment;

    private $constants;

    public function __construct($class)
    {
        $reflection = new \ReflectionClass($class);

        $this->className = $reflection->getName();
        $this->fileName = $reflection->getFileName();
        $this->docComment = $reflection->getDocComment();
        $this->parentClassName = $reflection->getParentClass()->name; // @todo undefined method, needs some refactoring
        $this->constants = $reflection->getConstants();

        $this->mappers = $this->parseMappers();
        $this->properties = $this->parseProperties();
    }

    public function getConstants()
    {
        return $this->constants;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Parse properties from annotations
     *
     * @return array Collection of \UniMapper\Reflection\Entity\Property
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    private function parseProperties()
    {
        preg_match_all(
            '#@property (.*?)\n#s',
            $this->docComment,
            $annotations
        );
        $properties = array();
        foreach ($annotations[0] as $annotation) {
            $property = new Entity\Property($annotation, $this);
            if (isset($properties[$property->getName()])) {
                throw new PropertyException(
                    "Duplicate property name $" . $property->getName(),
                    $this,
                    $annotation
                );
            }
            $properties[$property->getName()] = $property;
        }

        // Include inherited doc comments too
        if (stripos($this->docComment, "{@inheritDoc}") !== false) {
            $properties = array_merge($properties, $this->getEntityProperties($this->parentClassName)); // @todo
        }

        return $properties;
    }

    /**
     * Get defined class mappers from annotations
     *
     * @return array Collection of \UniMapper\Reflection\Entity\Mapper
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    private function parseMappers()
    {
        preg_match_all(
            '#@mapper (.*?)\n#s',
            $this->docComment,
            $annotations
        );
        $mappers = array();
        foreach ($annotations[0] as $annotation) {
            $mapperReflection = new Mapper(
                substr($annotation, 8),
                $this
            );
            if (isset($mappers[$mapperReflection->getName()])) {
                throw new PropertyException(
                    "Duplicate mapper definition!",
                    $this,
                    $annotation
                );
            }
            $mappers[$mapperReflection->getName()] = $mapperReflection;
        }
        return $mappers;
    }

    public function isHybrid()
    {
        return count($this->mappers) > 1;
    }

    public function getMappers()
    {
        return $this->mappers;
    }

    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            throw new \Exception("Unknown property " . $name . "!");
        }
        return $this->properties[$name];
    }

    public function getProperties($mapperName = null)
    {
        // Get all if mapping not defined
        if ($mapperName === null) {
            return $this->properties;
        }

        $properties = array();
        foreach ($this->properties as $property) {

            if ($property->getMapping() && $property->getMapping()->getName($mapperName) !== false) {
                $properties[$property->getName()] = $property;
            }
        }
        return $properties;
    }

    public function getPrimaryProperty()
    {
        foreach ($this->properties as $property) {
            if ($property->isPrimary()) {
                return $property;
            }
        }
        return null;
    }

}