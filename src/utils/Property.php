<?php

namespace UniMapper\Utils;

use UniMapper\EntityCollection,
    UniMapper\Exceptions\PropertyException,
    UniMapper\Exceptions\PropertyTypeException;

/**
 * Entity property definition
 */
class Property
{

    /** @var string */
    protected $type = null;

    /** @var string */
    protected $name = null;

    /** @var \UniMapper\Utils\Property\Mapping $mapping Mapping object */
    protected $mapping = null;

    /** @var array $basicTypes */
    protected $basicTypes = array("boolean", "integer", "double", "string", "array");

    /** @var \ReflectionClass $reflection Entity reflection */
    protected $reflection;

    /** @var \UniMapper\Utils\Property\Enumeration $enumeration */
    protected $enumeration = null;

    /** @var string $definition Raw property docblok definition */
    protected $rawDefinition;

    /** @var boolean $primary Is property defined as primary? */
    protected $primary = false;

    /**
     * Constructor
     *
     * @param string           $definition Property definition
     * @param \ReflectionClass $reflection Entity reflection class
     */
    public function __construct($definition, \ReflectionClass $reflection)
    {
        $this->rawDefinition = $definition;
        $this->reflection = $reflection;
        $arguments = preg_split('/\s+/', ltrim($definition, "@property "), null, PREG_SPLIT_NO_EMPTY);
        foreach ($arguments as $key => $argument) {
            if ($key === 0) {
                $this->readType($argument);
            } elseif ($key === 1) {
                $this->readName($argument);
            } else {
                $this->readFilters($argument);
            }
        }
    }

    /**
     * Get list of supported basic types
     *
     * @return array
     */
    public function getBasicTypes()
    {
        return $this->basicTypes;
    }

    /**
     * Get property name
     *
     * @return string
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function getName()
    {
        if ($this->name === null) {
            throw new PropertyException(
                "Property name is not set!",
                $this->reflection,
                $this->rawDefinition
            );
        }
        return $this->name;
    }

    /**
     * Get raw property definition
     *
     * @return string
     */
    public function getRawDefinition()
    {
        return $this->rawDefinition;
    }

    /**
     * Get entity reflection
     *
     * @return \ReflectionClass
     */
    public function getEntityReflection()
    {
        return $this->reflection;
    }

    /**
     * Read and set name from docblock definiton
     *
     * @param string $definition Docblok definition
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    protected function readName($definition)
    {
        $length = strlen($definition);
        if ($length === 1 || substr($definition, 0, 1) !== "$") {
            throw new PropertyException(
                "Invalid property name definition!",
                $this->reflection,
                $this->rawDefinition
            );
        }
        $this->name = substr($definition, 1, $length);
    }

    /**
     * Get property name
     *
     * @return string
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Get property type
     *
     * @return string
     *
     * @throws \UniMapper\Exceptions\PropertyTypeException
     */
    public function getType()
    {
        if ($this->type === null) {
            throw new PropertyTypeException(
                "Property type is not set!",
                $this->reflection,
                $this->rawDefinition
            );
        }
        return $this->type;
    }

    /**
     * Read and set property type from docblock definiton
     *
     * @param string $definition Docblok definition
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyTypeException
     */
    protected function readType($definition)
    {
        $basic = implode("|", $this->basicTypes);
        if (preg_match("#^($basic)$#", $definition)) {
            $this->type = $definition;
        } elseif (class_exists($definition)) {
            $this->type = $definition;
        } elseif (preg_match("#(.*?)\[\]#s", $definition)) {
            // Entity collection definition as UniMapper\Entity\Abc[] for example
            $entityClass = rtrim($definition, "[]");
            if (class_exists($entityClass)) {
                $collection = new EntityCollection($entityClass);
                $this->type = $collection;
            } else {
                throw new PropertyTypeException(
                    "Class " . $entityClass . " not found!",
                    $this->reflection,
                    $this->rawDefinition
                );
            }
        } else {
            throw new PropertyTypeException(
                "Unsupported type '" . $definition . "'!",
                $this->reflection,
                $this->rawDefinition
            );
        }
    }

    /**
     * Read and set filters from docblock definiton
     *
     * @param string $definition Property definition from docblok
     *
     * @return void
     */
    protected function readFilters($definition)
    {
        if (preg_match("#m:map\((.*?)\)#s", $definition, $matches)) {
            $this->mapping = new Property\Mapping(
                $this->name,
                $matches[1],
                $definition,
                $this->reflection
            );
        } elseif (preg_match("#m:enum\(([a-zA-Z0-9]+|self|parent)::([a-zA-Z0-9_]+)\*\)#", $definition, $matches)) {
            $this->enumeration = new Property\Enumeration(
                $matches,
                $definition,
                $this->reflection
            );
        } elseif (preg_match("#m:primary#s", $definition, $matches)) {
            $this->primary = true;
        }
    }

    /**
     * Validate property value type
     *
     * @param mixed $value Given value
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyTypeException
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function validateValue($value)
    {
        if ($this->type === null) {

            throw new PropertyException(
                "Property type is not set!",
                $this->reflection,
                $this->rawDefinition
            );
        }

        if ($this->enumeration !== null) {

            if (!$this->enumeration->isValueFromEnum($value)) {
                throw new PropertyTypeException(
                    "Value " . $value . " is not from defined entity enumeration"
                        . " range!",
                    $this->reflection,
                    $this->rawDefinition
                );
            }
        }

        $given = gettype($value);
        $expected = $this->type;

        if ($expected instanceof EntityCollection) {

            // Validate entity collection
            if ($given === "array") {

                foreach ($value as $item) {

                    $expectedEntityClass = $expected->getEntityClass();
                    if (!$item instanceof $expectedEntityClass) {
                        throw new PropertyTypeException(
                            "Array collection contains object type "
                                . get_class($item) . " but "
                                . "expected is " . $expectedEntityClass,
                            $this->reflection,
                            $this->rawDefinition
                        );
                    }
                }
            } elseif (!($value instanceof EntityCollection)) {

                throw new PropertyTypeException(
                    "Expected is array or UniMapper\EntityCollection but "
                        . $given . " given!",
                    $this->reflection,
                    $this->rawDefinition
                );
            }
        } elseif ($given === "object") {

            if (!($value instanceof $expected)) {
                throw new PropertyTypeException(
                    "Expected entity " . $expected . " but " . get_class($value)
                        . " given!",
                    $this->reflection,
                    $this->rawDefinition
                );
            }
        } elseif ($given !== "NULL" && $expected !== $given) {

            throw new PropertyTypeException(
                "Expected " . $expected . " but " . $given . " given!",
                $this->reflection,
                $this->rawDefinition
            );
        }
    }

    public function isPrimary()
    {
        return $this->primary;
    }

}