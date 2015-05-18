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

    /**
     * @param string $class   Entity class name
     *
     * @throws Exception\InvalidArgumentException
     * @throws Exception\EntityException
     */
    public function __construct($class)
    {
        $this->className = (string) $class;

        if (!is_subclass_of($this->className, "UniMapper\Entity")) {
            throw new Exception\InvalidArgumentException(
                "Class must be subclass of UniMapper\Entity but "
                . $this->className . " given!",
                $this->className
            );
        }

        $reflection = new \ReflectionClass($this->className);

        $this->fileName = $reflection->getFileName();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
            as $property
        ) {
            if ($property->isStatic()) {
                continue;
            }
            $this->publicProperties[] =  $property->getName();
        }

        // Register reflection to loader if needed
        if (!Loader::get($this->className)) {
            Loader::register($this);
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
    }

    public function createEntity($values = null)
    {
        $entityClass = $this->className;
        return new $entityClass($values);
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
        return UNC::classToName($this->className, UNC::ENTITY_MASK);
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

    /**
     * Get entity's related files
     *
     * @param array  $files
     *
     * @return array
     */
    public function getRelatedFiles(array $files = [])
    {
        if (in_array($this->fileName, $files)) {
            return $files;
        }

        $files[] = $this->fileName;
        foreach ($this->properties as $property) {
            if (in_array($property->getType(), [Property::TYPE_COLLECTION, Property::TYPE_ENTITY])) {
                $files += Loader::load($property->getTypeOption())->getRelatedFiles($files);
            }
        }
        return $files;
    }

    public function hasPrimary()
    {
        return $this->primaryName !== null;
    }

    /**
     * Get primary property reflection
     *
     * @return Property
     *
     * @throws \Exception
     */
    public function getPrimaryProperty()
    {
        if (!$this->hasPrimary()) {
            throw new \Exception(
                "Primary property not defined in " . $this->className . "!"
            );
        }
        return $this->properties[$this->primaryName];
    }

}
