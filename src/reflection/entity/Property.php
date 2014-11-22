<?php

namespace UniMapper\Reflection\Entity;

use UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection,
    UniMapper\Association,
    UniMapper\NamingConvention as UNC,
    UniMapper\Exception;

/**
 * Reflection object of single property from entity reflection
 */
class Property
{

    const TYPE_DATETIME = "DateTime";

    /** @var string */
    private $entityClass;

    /** @var string $type */
    private $type;

    /** @var string $name */
    private $name;

    /** @var Reflection\Entity\Property\Mapping */
    private $mapping;

    /** @var array $basicTypes */
    private $basicTypes = ["boolean", "integer", "double", "string", "array"];

    /** @var Reflection\Entity */
    private $entityReflection;

    /** @var Entity\Property\Enumeration $enumeration */
    private $enumeration;

    /** @var string $definition Raw property docblok definition */
    private $rawDefinition;

    /** @var boolean $primary Is property defined as primary? */
    private $primary = false;

    /** @var boolean $computed Is property computed? */
    private $computed = false;

    /** @var Association $association */
    private $association;

    /** @var array $associations List of available associations */
    private $associations = [
        "UniMapper\Association\ManyToOne",
        "UniMapper\Association\ManyToMany",
        "UniMapper\Association\OneToOne",
        "UniMapper\Association\OneToMany"
    ];

    /** @var boolean $writable */
    private $writable = true;

    /**
     * @param string            $rawDefinition
     * @param Reflection\Entity $entityReflection
     */
    public function __construct($rawDefinition, Reflection\Entity $entityReflection)
    {
        $this->rawDefinition = $rawDefinition;
        $this->entityReflection = $entityReflection;
        $this->entityClass = $entityReflection->getClassName();

        $arguments = preg_split('/\s+/', $rawDefinition, null, PREG_SPLIT_NO_EMPTY);

        // read only property
        if ($arguments[0] === "-read") {
            $this->writable = false;
            array_shift($arguments);
        }

        $this->_parseType($arguments[0]);
        next($arguments);

        $this->_parseName($arguments[1]);
        next($arguments);

        foreach ($arguments as $argument) {
            $this->_parseOptions($argument);
        }

        $this->_validateDefinition();
    }

    private function _validateDefinition()
    {
        if ($this->computed
            && ($this->mapping || $this->enumeration || $this->primary)
        ) {
            throw new Exception\PropertyException(
                "Computed property can not be combined with mapping, enumeration"
                . " or primary!",
                $this->entityClass,
                $this->rawDefinition
            );
        }

        if ($this->association
            && ($this->computed || $this->mapping || $this->enumeration)
        ) {
            throw new Exception\PropertyException(
                "Association can not be combined with mapping, computed or "
                . "enumeration!",
                $this->entityClass,
                $this->rawDefinition
            );
        }
    }

    public function getRawDefinition()
    {
        return $this->rawDefinition;
    }

    public function isWritable()
    {
        return $this->writable;
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
     * @throws Exception\PropertyException
     */
    private function _parseName($definition)
    {
        $length = strlen($definition);
        if ($length === 1 || substr($definition, 0, 1) !== "$") {
            throw new Exception\PropertyException(
                "Invalid property name definition!",
                $this->entityClass,
                $this->rawDefinition
            );
        }
        $this->name = substr($definition, 1, $length);
    }

    /**
     * Get property name
     *
     * @return string
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    /**
     * Get property name
     *
     * @param bool $unmapped
     *
     * @return string
     */
    public function getName($unmapped = false)
    {
        if ($unmapped && $this->mapping && $this->mapping->getName()) {
            return $this->mapping->getName();
        }
        return $this->name;
    }

    /**
     * Get property mapping
     *
     * @return \UniMapper\Reflection\Entity\Property\Mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Get property type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Read and set property type from docblock definiton
     *
     * @param string $definition Docblok definition
     *
     * @throws Exception\PropertyException
     */
    private function _parseType($definition)
    {
        $basic = implode("|", $this->basicTypes);
        if (preg_match("#^(" . $basic . ")$#", $definition)) {
            // Basic type

            return $this->type = $definition;
        } elseif ($definition === self::TYPE_DATETIME) {
            // DateTime

            return $this->type = $definition;
        } elseif (class_exists(UNC::nameToClass($definition, UNC::$entityMask))) {
            // Entity

            return $this->type = $this->_loadEntityReflection(
                UNC::nameToClass($definition, UNC::$entityMask)
            );
        } elseif (preg_match("#(.*?)\[\]#s", $definition)) {
            // Collection

            try {
                $entityReflection = $this->_loadEntityReflection(
                    UNC::nameToClass(rtrim($definition, "[]"), UNC::$entityMask)
                );
            } catch (Exception\InvalidArgumentException $exception) {

            }
            return $this->type = new EntityCollection($entityReflection);
        }

        throw new Exception\PropertyException(
            "Unsupported type '" . $definition . "'!",
            $this->entityClass,
            $this->rawDefinition
        );
    }

    /**
     * Load lazy entity reflection
     *
     * @param string $entityClass
     *
     * @return Reflection\Entity
     */
    private function _loadEntityReflection($entityClass)
    {
        if ($this->entityReflection->getClassName() === $entityClass) {
            return $this->entityReflection;
        } elseif (isset($this->entityReflection->getRelated()[$entityClass])) {
            return $this->entityReflection->getRelated()[$entityClass];
        }

        $related = $this->entityReflection->getRelated();
        $related[$this->entityReflection->getClassName()]
            = $this->entityReflection;

        $reflection = new Reflection\Entity($entityClass, $related);

        $this->entityReflection->addRelated($reflection);

        return $reflection;
    }

    /**
     * Read and set options from docblock definiton
     *
     * @param string $definition
     */
    private function _parseOptions($definition)
    {
        if (preg_match("#m:computed#s", $definition, $matches)) {
            // m:computed

            $computedMethodName = $this->getComputedMethodName();
            if (!method_exists($this->entityReflection->getClassName(), $computedMethodName)) {
                throw new Exception\PropertyException(
                    "Can not find computed method with name "
                    . $computedMethodName . "!",
                   $this->entityClass,
                    $this->rawDefinition
                );
            }
            $this->computed = true;
        } elseif (preg_match(Property\Mapping::EXPRESSION, $definition, $matches)) {
            // Mapping

            if ($this->mapping) {
                throw new Exception\PropertyException(
                    "Mapping already defined!",
                   $this->entityClass,
                    $this->rawDefinition
                );
            }

            try {
                $this->mapping = new Property\Mapping(
                    $this->entityReflection->getClassName(),
                    $matches[1]
                );
            } catch (Exception\DefinitionException $e) {
                throw new Exception\PropertyException(
                    $e->getMessage(),
                   $this->entityClass,
                    $this->rawDefinition
                );
            }
        } elseif (preg_match(Property\Enumeration::EXPRESSION, $definition, $matches)) {
            // Enumeration

            try {
                $this->enumeration = new Property\Enumeration(
                    $matches,
                    $this->entityClass
                );
            } catch (Exception\DefinitionException $e) {
                throw new Exception\PropertyException(
                    $e->getMessage(),
                    $this->entityClass,
                    $this->rawDefinition
                );
            }
        } elseif (preg_match("#m:primary#s", $definition, $matches)) {
            // Primary

            $this->primary = true;
        } elseif (preg_match("#m:assoc\((.*?)\)#s", $definition, $matches)) {
            // Association

            if (!$this->entityReflection->hasAdapter()) {
                throw new Exception\PropertyException(
                    "Can not use associations while entity "
                    . $this->entityClass
                    . " has no adapter defined!",
                    $this->entityClass,
                    $this->rawDefinition
                );
            }

            // Get target entity class
            if ($this->type instanceof EntityCollection) {
                $targetEntityReflection = $this->type->getEntityReflection();
            } elseif ($this->type instanceof Reflection\Entity) {
                $targetEntityReflection = $this->type;
            } else {
                throw new Exception\PropertyException(
                    "Property type must be collection or entity if association "
                    . "defined!",
                    $this->entityClass,
                    $this->rawDefinition
                );
            }
            if (!$targetEntityReflection->hasAdapter()) {
                throw new Exception\PropertyException(
                    "Can not use associations while target entity "
                    . $targetEntityReflection->getClassName()
                    . " has no adapter defined!",
                    $this->entityClass,
                    $this->rawDefinition
                );
            }

            foreach ($this->associations as $assocClass) {

                try {

                    $this->association = new $assocClass(
                        $this,
                        $targetEntityReflection,
                        $matches[1]
                    );
                    break;
                } catch (Exception\DefinitionException $e) {

                    if ($e->getCode() !== Exception\DefinitionException::DO_NOT_FAIL) {
                        throw new Exception\PropertyException(
                            $e->getMessage(),
                            $this->entityClass,
                            $this->rawDefinition
                        );
                    }
                }
            }

            if (!$this->association) {
                throw new Exception\PropertyException(
                    "Unrecognized association m:assoc(" . $matches[1] . ")!",
                    $this->entityClass,
                    $this->rawDefinition
                );
            }

            if ($this->association instanceof Association\Multi
                && !$this->type instanceof EntityCollection
            ) {
                throw new Exception\PropertyException(
                    "Type must be entity collection! " . $this->name .$this->rawDefinition,
                    $this->entityClass,
                    $this->rawDefinition
                );
            } elseif ($this->association instanceof Association\Single
                && !$this->type instanceof Reflection\Entity
            ) {
                throw new Exception\PropertyException(
                    "Type must be entity!",
                    $this->entityClass,
                    $this->rawDefinition
                );
            }
        }
    }

    /**
     * Validate value type
     *
     * @param mixed $value Given value
     *
     * @throws Exception\PropertyValueException
     * @throws \Exception
     */
    public function validateValueType($value)
    {
        $expectedType = $this->type;

        // Enumeration
        if ($this->enumeration && !$this->enumeration->isValid($value)) {
            throw new Exception\PropertyValueException(
                "Value " . $value . " is not from defined entity enumeration "
                . "range on property " . $this->name . "!",
                $this->entityClass,
                $this->rawDefinition,
                Exception\PropertyValueException::ENUMERATION
            );
        }

        // Basic type
        if ($this->isTypeBasic()) {

            if (gettype($value) === $expectedType) {
                return;
            }
            throw new Exception\PropertyValueException(
                "Expected " . $expectedType . " but " . gettype($value)
                . " given on property " . $this->name . "!",
                $this->entityClass,
                $this->rawDefinition,
                Exception\PropertyValueException::TYPE
            );
        }

        // Object validation
        $givenType = gettype($value);
        if ($givenType === "object") {
            $givenType = get_class($value);
        }

        if ($expectedType instanceof Reflection\Entity) {
            // Entity

            $expectedType = $expectedType->getClassName();
            if ($value instanceof $expectedType) {
                return;
            } else {
                throw new Exception\PropertyValueException(
                    "Expected entity " . $expectedType . " but " . $givenType
                    . " given on property " . $this->name . "!",
                    $this->entityClass,
                    $this->rawDefinition,
                    Exception\PropertyValueException::TYPE
                );
            }

        } elseif ($expectedType instanceof EntityCollection) {
            // Collection

            if (!$value instanceof EntityCollection) {

                throw new Exception\PropertyValueException(
                    "Expected entity collection but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $this->entityClass,
                    $this->rawDefinition,
                    Exception\PropertyValueException::TYPE
                );
            } elseif ($value->getEntityReflection()->getClassName() !== $expectedType->getEntityReflection()->getClassName()) {
                throw new Exception\PropertyValueException(
                    "Expected collection of entity "
                    . $expectedType->getEntityReflection()->getClassName()
                    . " but collection of entity "
                    . $value->getEntityReflection()->getClassName()
                    . " given on property " . $this->name . "!",
                    $this->entityClass,
                    $this->rawDefinition,
                    Exception\PropertyValueException::TYPE
                );
            } else {
                return;
            }

        } elseif ($expectedType === self::TYPE_DATETIME) {
            // DateTime

            if ($value instanceof \DateTime) {
                return;
            } else {
                throw new Exception\PropertyValueException(
                    "Expected DateTime but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $this->entityClass,
                    $this->rawDefinition,
                    Exception\PropertyValueException::TYPE
                );
            }
        }

        throw new Exception\UnexpectedException(
            "Expected " . $expectedType . " but " . $givenType . " given on "
            . "property " . $this->name . ". It could be an internal ORM error!"
        );
    }

    /**
     * Try to convert value on required type automatically
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     */
    public function convertValue($value)
    {
        if ($this->isTypeBasic()) {
            // Basic

            if ($this->type === "boolean" && strtolower($value) === "false") {
                return false;
            }

            if (settype($value, $this->type)) {
                return $value;
            }
        } elseif ($this->type === self::TYPE_DATETIME) {
            // DateTime

            if ($value instanceof \DateTime) {
                return $value;
            } elseif (is_array($value) && isset($value["date"])) {
                $date = $value["date"];
            } elseif (is_object($value) && isset($value->date)) {
                $date = $value->date;
            } else {
                $date = $value;
            }

            if (isset($date)) {
                try {
                    return new \DateTime($date);
                } catch (\Exception $e) {

                }
            }
        } elseif ($this->type instanceof EntityCollection
            && Validator::isTraversable($value)
        ) {
            // Collection

            $collection = clone $this->type;
            foreach ($value as $index => $data) {

                $collection[$index] = $this->type->getEntityReflection()
                    ->createEntity($data);
            }
            return $collection;
        } elseif ($this->type instanceof Reflection\Entity
            && Validator::isTraversable($value)
        ) {
            // Entity

            return $this->type->createEntity($value);
        }

        throw new Exception\InvalidArgumentException(
            "Can not convert value on property '" . $this->name
            . "' automatically!"
        );
    }

    public function isTypeBasic()
    {
        return in_array($this->type, $this->getBasicTypes());
    }

    public function isTypeEntity()
    {
        return $this->type instanceof Reflection\Entity;
    }

    public function isTypeCollection()
    {
        return $this->type instanceof EntityCollection;
    }

    public function isComputed()
    {
        return $this->computed;
    }

    public function isPrimary()
    {
        return $this->primary;
    }

    public function isAssociation()
    {
        return $this->association !== null;
    }

    public function getAssociation()
    {
        return $this->association;
    }

    public function getComputedMethodName()
    {
        return "compute" . ucfirst($this->name);
    }

}