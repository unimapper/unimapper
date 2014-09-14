<?php

namespace UniMapper\Reflection;

use UniMapper\Exception;

/**
 * Entity reflection
 */
class Entity
{

    /** @var \UniMapper\Reflection\Entity\Adapter */
    private $adapter;

    /** @var array */
    private $properties = [];

    /** @var array $publicProperties List of public property names */
    private $publicProperties = [];

    /** @var string */
    private $className;

    /** @var string */
    private $parentClassName;

    /** @var string */
    private $fileName;

    /** @var string */
    private $docComment;

    /** @var array */
    private $constants;

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
        $this->docComment = $reflection->getDocComment();

        // @todo undefined method, needs some refactoring
        $this->parentClassName = $reflection->getParentClass()->name;

        $this->constants = $reflection->getConstants();

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC)
            as $property
        ) {
            $this->publicProperties[] =  $property->getName();
        }

        $this->adapter = $this->_parseAdapter();
        $this->_parseProperties();
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

    public function getRelated()
    {
        return $this->related;
    }

    /**
     * Parse properties from annotations
     *
     * @return array Collection of \UniMapper\Reflection\Entity\Property with
     *               property name as index.
     *
     * @throws Exception\PropertyException
     */
    private function _parseProperties()
    {
        preg_match_all(
            '/\s*\*\s*@property([ -](read)*\s*.*)/',
            $this->docComment,
            $annotations
        );
        $properties = [];
        foreach ($annotations[1] as $index => $definition) {

            $property = new Entity\Property($definition, $this);

            // Prevent duplications
            if (isset($properties[$property->getName()])) {
                throw new Exception\PropertyException(
                    "Duplicate property with name '" . $property->getName() . "'!",
                    $this,
                    $definition
                );
            }
            if (in_array($property->getName(), $this->publicProperties)) {
                throw new Exception\PropertyException(
                    "Property '" . $property->getName()
                    . "' already defined as public property!",
                    $this,
                    $definition
                );
            }

            // Primary property
            if ($property->isPrimary() && $this->primaryPropertyName !== null) {
                throw new Exception\PropertyException(
                    "Primary property already defined!",
                    $this,
                    $annotation
                );
            } elseif ($property->isPrimary()) {
                $this->primaryPropertyName = $property->getName();
            }
            if ($property->isAssociation() && $this->primaryPropertyName === null) {
                throw new Exception\PropertyException(
                    "You must define primary property before the association!",
                    $this,
                    $annotation
                );
            }

            $this->properties[$property->getName()] = $property;
        }

        // Include inherited doc comments too
        if (stripos($this->docComment, "{@inheritDoc}") !== false) {
            $this->properties = array_merge(
                $this->properties,
                $this->getEntityProperties($this->parentClassName)
            ); // @todo broken
        }
    }

    /**
     * Get adapter definition from annotations
     *
     * @return \UniMapper\Reflection\Entity\Mapper|null
     */
    private function _parseAdapter()
    {
        preg_match_all(
            '#@adapter (.*?)\n#s',
            $this->docComment,
            $annotations
        );

        if (empty($annotations[0])) {
            return;
        }

        if (count($annotations[0]) > 1) {
            throw new Exception\PropertyException(
                "Only one adapter definition allowed!",
                $this,
                $annotations[0][1]
            );
        }

        try {
            return new Adapter(substr($annotations[0][0], 8), $this);
        } catch (Exception\DefinitionException $e) {
            throw new Exception\PropertyException(
                $e->getMessage(),
                $this,
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
