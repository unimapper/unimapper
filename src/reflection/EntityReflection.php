<?php

namespace UniMapper\Reflection;

use UniMapper\Utils\AnnotationParser;

/**
 * Entity reflection
 */
class EntityReflection extends \ReflectionClass
{

    protected $mappers;
    protected $properties;

    public function __construct($argument)
    {
        parent::__construct($argument);

        $class = $this->getName();
        $this->mappers = AnnotationParser::getEntityMappers($class);
        $this->properties = AnnotationParser::getEntityProperties($class);
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
            throw new Exception("Uknown property " . $name . "!");
        }
        return $this->properties[$name];
    }

    public function getProperties($mapperName = null)
    {
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