<?php

namespace UniMapper\Reflection;

use UniMapper\Cache\ICache,
    UniMapper\Exceptions\PropertyException;

/**
 * Entity reflection
 */
class Entity extends \ReflectionClass
{

    protected $mappers = null;
    protected $properties = null;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    public function __construct($class, ICache $cache = null)
    {
        parent::__construct($class);
        $this->cache = $cache;
    }

    /**
     * Parse properties from annotations
     *
     * @return array Collection of \UniMapper\Reflection\Entity\Property
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    protected function parseProperties()
    {
        $classDoc = $this->getDocComment();
        preg_match_all(
            '#@property (.*?)\n#s',
            $classDoc,
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
        if (stripos($classDoc, "{@inheritDoc}") !== false) {
            $properties = array_merge(
                $properties,
                $this->getEntityProperties($this->getParentClass()->name) // @todo undefined method, needs some refactoring
            );
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
    protected function parseMappers()
    {
        $classDoc = $this->getDocComment();
        preg_match_all(
            '#@mapper (.*?)\n#s',
            $classDoc,
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
        // Lazy load
        if ($this->mappers === null) {

            if ($this->cache) {

                $key = "mappers-" . $this->getName();
                $this->mappers = $this->cache->load($key);
                if (!$this->mappers) {
                    $this->mappers = $this->parseMappers();
                    $this->cache->save($key, $this->mappers, $this->getFileName());
                }
            } else {
                $this->mappers = $this->parseMappers();
            }
        }

        return $this->mappers;
    }

    public function hasProperty($name)
    {
        $properties = $this->getProperties();
        return isset($properties[$name]);
    }

    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            throw new Exception("Uknown property " . $name . "!");
        }
        return $this->properties[$name];
    }

    public function getProperties($mapperName = null)
    {
        // Lazy load
        if ($this->properties === null) {

            if ($this->cache) {

                $key = "properties-" . $this->getName();
                $this->properties = $this->cache->load($key);
                if (!$this->properties) {
                    $this->properties = $this->parseProperties();
                    $this->cache->save($key, $this->properties, $this->getFileName());
                }
            } else {
                $this->properties = $this->parseProperties();
            }
        }

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
        foreach ($this->getProperties() as $property) {
            if ($property->isPrimary()) {
                return $property;
            }
        }
        return null;
    }

}