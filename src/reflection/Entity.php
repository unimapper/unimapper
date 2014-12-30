<?php

namespace UniMapper\Reflection;

use UniMapper\Exception,
    UniMapper\NamingConvention as UNC;

class Entity
{

    /** @var string */
    private $adapterName;

    /** @var string */
    private $adapterResource;

    /** @var array */
    private $properties = [];

    /** @var array $publicProperties List of public property names */
    private $publicProperties = [];

    /** @var string */
    private $className;

    /** @var string */
    private $fileName;

    /** @var string */
    private $primaryName;

    /** @var boolean */
    private $initialized = false;

    /** @var array $related List of related entity reflections */
    private $related = [];

    /**
     * @param string $class   Entity class name
     * @param array  $related Related reflections
     *
     * @throws \Exception
     */
    public function __construct($class, array $related = [])
    {
        $this->className = (string) $class;

        foreach ($related as $reflection) {

            if (!$reflection instanceof Entity) {
                throw new Exception\InvalidArgumentException(
                    "Related reflection must be entity reflection instance!"
                );
            }
            $this->related[$reflection->getClassName()] = $reflection;
        }

        if (!$this->initialized) {
            $this->_initialize();
        }
    }

    private function _initialize()
    {
        if (!is_subclass_of($this->className, "UniMapper\Entity")) {
            throw new Exception\InvalidArgumentException(
                "Class must be subclass of UniMapper\Entity but "
                . $this->className . " given!"
            );
        }

        $reflection = new \ReflectionClass($this->className);

        $this->fileName = $reflection->getFileName();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
            as $property
        ) {
            $this->publicProperties[] =  $property->getName();
        }

        $docComment = $reflection->getDocComment();

        // Parse adapter
        try {

            $adapter = AnnotationParser::parseAdapter($docComment);
            if ($adapter) {
                list($this->adapterName, $this->adapterResource) = $adapter;
            }
        } catch (Exception\AnnotationException $e) {
            throw new Exception\EntityException(
                $e->getMessage(),
                $this->className,
                $e->getDefinition()
            );
        }

        // Parse properties
        $this->_parseProperties($docComment);

        $this->initialized = true;
    }

    /**
     * Add related entity reflection
     *
     * @param \UniMapper\Reflection\Entity $reflection
     */
    public function addRelated(Entity $reflection)
    {
        $this->related[$reflection->getClassName()] = $reflection;
    }

    public function createEntity($values = [])
    {
        $entityClass = $this->className;
        return new $entityClass($this, $values);
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getName()
    {
        return UNC::classToName($this->className, UNC::$entityMask);
    }

    public function getRelated()
    {
        return $this->related;
    }

    /**
     * Get related entity class files
     *
     * @param array $files
     *
     * @return array
     */
    public function getRelatedFiles(array $files = [])
    {
        foreach ($this->related as $childReflection) {

            $fileName = $childReflection->getFileName();
            if (!array_search($fileName, $files, true)) {

                $files[] = $fileName;
                if ($childReflection->getRelated()) {
                    $files = array_merge($files, $childReflection->getRelatedFiles($files));
                }
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * Parse properties from annotations
     *
     * @param string $docComment
     *
     * @throws Exception\EntityException
     */
    private function _parseProperties($docComment)
    {
        $properties = [];
        foreach (AnnotationParser::parseProperties($docComment) as $definition) {

            try {
                $property = new Property(
                    $definition[2],
                    $definition[3],
                    $this,
                    !$definition[1],
                    $definition[4]
                );
            } catch (Exception\PropertyException $e) {
                throw new Exception\EntityException(
                    $e->getMessage(),
                    $this->className,
                    $definition[0]
                );
            }

            // Prevent duplications
            if (isset($properties[$property->getName()])) {
                throw new Exception\EntityException(
                    "Duplicate property with name '" . $property->getName() . "'!",
                    $this->className,
                    $definition[0]
                );
            }
            if (in_array($property->getName(), $this->publicProperties)) {
                throw new Exception\EntityException(
                    "Property '" . $property->getName()
                    . "' already defined as public property!",
                    $this->className,
                    $definition[0]
                );
            }

            // Primary property
            if ($property->hasOption(Property::OPTION_PRIMARY)) {

                if ($this->hasPrimary()) {
                    throw new Exception\EntityException(
                        "Primary already defined!",
                        $this->className,
                        $definition[0]
                    );
                }
                $this->primaryName = $property->getName();
            }

            if ($property->hasOption(Property::OPTION_ASSOC) && $this->primaryName === null) {
                throw new Exception\EntityException(
                    "You must define primary property before the association!",
                    $this->className,
                    $definition[0]
                );
            }

            $this->properties[$property->getName()] = $property;
        }
    }

    public function getAdapterName()
    {
        return $this->adapterName;
    }

    public function getAdapterResource()
    {
        return $this->adapterResource;
    }

    public function hasAdapter()
    {
        return !empty($this->adapterName);
    }

    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * Get property reflection object
     *
     * @param string $name
     *
     * @return \UniMapper\Reflection\Property
     *
     * @throws Exception\InvalidArgumentException
     */
    public function getProperty($name)
    {
        if (!$this->hasProperty($name)) {
            throw new Exception\InvalidArgumentException(
                "Unknown property " . $name . "!"
            );
        }
        return $this->properties[$name];
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getPublicProperties()
    {
        return $this->publicProperties;
    }

    public function hasPrimary()
    {
        return $this->primaryName !== null;
    }

    /**
     * Get primary property reflection
     *
     * @return \UniMapper\Reflection\Property
     *
     * @throws Exception\UnexpectedException
     */
    public function getPrimaryProperty()
    {
        if (!$this->hasPrimary()) {
            throw new Exception\UnexpectedException(
                "Primary property not defined in " . $this->className . "!"
            );
        }
        return $this->properties[$this->primaryName];
    }

}
