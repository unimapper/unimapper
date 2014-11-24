<?php

namespace UniMapper\Reflection;

use UniMapper\Exception;

/**
 * Entity reflection
 */
class Entity
{

    /** @var \UniMapper\Reflection\Adapter */
    private $adapter;

    /** @var array */
    private $properties = [];

    /** @var array $publicProperties List of public property names */
    private $publicProperties = [];

    /** @var string */
    private $className;

    /** @var string */
    private $fileName;

    /** @var string */
    private $primaryPropertyName;

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
        $this->adapter = $this->_parseAdapter($docComment);
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
     * @return array Collection of \UniMapper\Reflection\Entity\Property with
     *               property name as index.
     *
     * @throws Exception\PropertyException
     */
    private function _parseProperties($docComment)
    {
        preg_match_all(
            '/\s*\*\s*@property([ -](read)*\s*.*)/',
            $docComment,
            $annotations
        );
        $properties = [];
        foreach ($annotations[1] as $definition) {

            $property = new Entity\Property($definition, $this);

            // Prevent duplications
            if (isset($properties[$property->getName()])) {
                throw new Exception\PropertyException(
                    "Duplicate property with name '" . $property->getName() . "'!",
                    $this->className,
                    $definition
                );
            }
            if (in_array($property->getName(), $this->publicProperties)) {
                throw new Exception\PropertyException(
                    "Property '" . $property->getName()
                    . "' already defined as public property!",
                    $this->className,
                    $definition
                );
            }

            // Primary property
            if ($property->isPrimary() && $this->primaryPropertyName !== null) {
                throw new Exception\PropertyException(
                    "Primary property already defined!",
                    $this->className,
                    $annotation
                );
            } elseif ($property->isPrimary()) {
                $this->primaryPropertyName = $property->getName();
            }
            if ($property->isAssociation() && $this->primaryPropertyName === null) {
                throw new Exception\PropertyException(
                    "You must define primary property before the association!",
                    $this->className,
                    $annotation
                );
            }

            $this->properties[$property->getName()] = $property;
        }
    }

    /**
     * Get adapter definition from annotations
     *
     * @param string $docComment
     *
     * @return \UniMapper\Reflection\Entity\Adapter|null
     */
    private function _parseAdapter($docComment)
    {
        preg_match_all(
            '#@adapter (.*?)\n#s',
            $docComment,
            $annotations
        );

        if (empty($annotations[0])) {
            return;
        }

        if (count($annotations[0]) > 1) {
            throw new Exception\PropertyException(
                "Only one adapter definition allowed!",
                $this->className,
                $annotations[0][1]
            );
        }

        try {
            return new Adapter(substr($annotations[0][0], 8), $this);
        } catch (Exception\DefinitionException $e) {
            throw new Exception\PropertyException(
                $e->getMessage(),
                $this->className,
                $annotations[0][1]
            );
        }
    }

    public function hasAdapter()
    {
        return $this->adapter instanceof Adapter;
    }

    public function getAdapterReflection()
    {
        return $this->adapter;
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
     * @return \UniMapper\Reflection\Entity\Property
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

    public function hasPrimaryProperty()
    {
        return $this->primaryPropertyName !== null;
    }

    /**
     * Get primary property reflection
     *
     * @return \UniMapper\Reflection\Entity\Property
     *
     * @throws Exception\UnexpectedException
     */
    public function getPrimaryProperty()
    {
        if (!$this->hasPrimaryProperty()) {
            throw new Exception\UnexpectedException(
                "Primary property not defined in " . $this->className . "!"
            );
        }
        return $this->properties[$this->primaryPropertyName];
    }

}
