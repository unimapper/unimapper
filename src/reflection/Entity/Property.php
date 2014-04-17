<?php

namespace UniMapper\Reflection\Entity;

use UniMapper\EntityCollection,
    UniMapper\Reflection,
    UniMapper\Exceptions\PropertyException,
    UniMapper\Exceptions\PropertyTypeException;

/**
 * Entity property reflection
 */
class Property
{

    /** @var string */
    protected $type = null;

    /** @var string */
    protected $name = null;

    /** @var \UniMapper\Reflection\Entity\Property\Mapping $mapping Mapping object */
    protected $mapping = null;

    /** @var array $basicTypes */
    protected $basicTypes = array("boolean", "integer", "double", "string", "array");

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    /** @var \UniMapper\Reflection\Entity\Property\Enumeration $enumeration */
    protected $enumeration = null;

    /** @var string $definition Raw property docblok definition */
    protected $rawDefinition;

    /** @var boolean $primary Is property defined as primary? */
    protected $primary = false;

    /** @var \UniMapper\Reflection\Entity\Property\Validators */
    protected $validators;

    /** @var boolean $computed Is property computed? */
    protected $computed = false;

    public function __construct($definition, Reflection\Entity $entityReflection)
    {
        $this->rawDefinition = $definition;
        $this->entityReflection = $entityReflection;
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
                $this->entityReflection,
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
     * @return \UniMapper\Reflection\Entity
     */
    public function getEntityReflection()
    {
        return $this->entityReflection;
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
                $this->entityReflection,
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
                $this->entityReflection,
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
                    $this->entityReflection,
                    $this->rawDefinition
                );
            }
        } else {
            throw new PropertyTypeException(
                "Unsupported type '" . $definition . "'!",
                $this->entityReflection,
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
        if (preg_match("#m:computed#s", $definition, $matches)) {
            // m:computed

            $computedMethodName = $this->getComputedMethodName();
            if (!method_exists($this->entityReflection->getClassName(), $computedMethodName)) {
                throw new PropertyException("Can not find computed method with name " . $computedMethodName . "!", $this->entityReflection, $definition);
            }
            $this->computed = true;
        } elseif (preg_match("#m:map\((.*?)\)#s", $definition, $matches)) {
            // m:map(Mapper:)
            // m:map(Mapper1:column|Mapper2:column)

            if ($this->computed) {
                throw new PropertyException("Can not combine m:computed with m:map!", $this->entityReflection, $definition);
            }
            $this->mapping = new Property\Mapping($this->name, $matches[1], $definition, $this->entityReflection);
        } elseif (preg_match("#m:enum\(([a-zA-Z0-9]+|self|parent)::([a-zA-Z0-9_]+)\*\)#", $definition, $matches)) {
            // m:enum(self::CUSTOM_*)
            // m:enum(parent::CUSTOM_*)
            // m:enum(MY_CLASS::CUSTOM_*)

            if ($this->computed) {
                throw new PropertyException("Can not combine m:computed with m:enum!", $this->entityReflection, $definition);
            }
            $this->enumeration = new Property\Enumeration($matches, $definition, $this->entityReflection);
        } elseif (preg_match("#m:primary#s", $definition, $matches)) {
            // m:primary

            if ($this->computed) {
                throw new PropertyException("Can not combine m:computed with m:primary!", $this->entityReflection, $definition);
            }
            $this->primary = true;
        } elseif (preg_match("#m:validate\((.*?)\)#s", $definition, $matches)) {
            // m:validate:(url)
            // m:validate:(ipv4|ipv6)

            if ($this->computed) {
                throw new PropertyException("Can not combine m:computed with m:validate!", $this->entityReflection, $definition);
            }
            $this->validators = new Property\Validators($matches[1], $definition, $this->entityReflection);
        }
    }

    /**
     * Validate property value type
     *
     * @param mixed $value Given value
     *
     * @throws \UniMapper\Exceptions\PropertyException
     * @throws \UniMapper\Exceptions\PropertyTypeException
     * @throws \Exception
     */
    public function validateValue($value)
    {
        $expectedType = $this->type;

        if ($expectedType === null) { // @todo check entity validity first => move out
            throw new PropertyException("Property type not defined on property " . $this->name . "!", $this->entityReflection, $this->rawDefinition);
        }

        // Validators
        if ($this->validators) {

            foreach ($this->validators->getCallbacks() as $callback) {
                if (!call_user_func_array($callback, [$value])) {
                    throw new PropertyTypeException("Value " . $value . " is not valid for " . $callback[0] . "::" . $callback[1] . " on property " . $this->name . "!", $this->entityReflection, $this->rawDefinition);
                }
            }
        }

        // Enumeration
        if ($this->enumeration !== null && !$this->enumeration->isValueFromEnum($value)) {
            throw new PropertyTypeException("Value " . $value . " is not from defined entity enumeration range on property " . $this->name . "!", $this->entityReflection, $this->rawDefinition);
        }

        // Basic type
        if ($this->isBasicType()) {

            if (gettype($value) === $expectedType) {
                return;
            }
            throw new PropertyTypeException("Expected " . $expectedType . " but " . gettype($value) . " given on property " . $this->name . "!", $this->entityReflection, $this->rawDefinition);
        }

        // Object
        if (is_object($expectedType)) {
            $expectedType = get_class($expectedType);
        }

        if (class_exists($expectedType)) {

            if ($value instanceof $expectedType) {
                return;
            }

            $givenType = gettype($value);
            if ($givenType === "object") {
                $givenType = get_class($value);
            }
            throw new PropertyTypeException("Expected " . $expectedType . " but " . $givenType . " given on property " . $this->name . "!", $this->entityReflection, $this->rawDefinition);
        }

        $givenType = gettype($value);
        if ($givenType === "object") {
            $givenType = get_class($value);
        }
        throw new \Exception("Expected " . $expectedType . " but " . $givenType . " given on property " . $this->name . ". It could be an internal ORM error!");
    }

    public function isBasicType()
    {
        return in_array($this->type, $this->getBasicTypes());
    }

    public function isComputed()
    {
        return $this->computed;
    }

    public function isPrimary()
    {
        return $this->primary;
    }

    public function getComputedMethodName()
    {
       return "compute" . ucfirst($this->name);
    }

}