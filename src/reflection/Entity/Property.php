<?php

namespace UniMapper\Reflection\Entity;

use UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection,
    UniMapper\NamingConvention as NC,
    UniMapper\Exception;

/**
 * Entity property reflection
 */
class Property
{

    const TYPE_DATETIME = "DateTime";

    /** @var string */
    protected $type;

    /** @var string */
    protected $name;

    /** @var \UniMapper\Reflection\Entity\Property\Mapping */
    protected $mapping;

    /** @var array $basicTypes */
    protected $basicTypes = ["boolean", "integer", "double", "string", "array"];

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    /** @var \UniMapper\Reflection\Entity\Property\Enumeration $enumeration */
    protected $enumeration;

    /** @var string $definition Raw property docblok definition */
    protected $rawDefinition;

    /** @var boolean $primary Is property defined as primary? */
    protected $primary = false;

    /** @var boolean $computed Is property computed? */
    protected $computed = false;

    /** @var \UniMapper\Reflection\Entity\Property\Association $association */
    protected $association;

    /** @var array $associations List of registered associations */
    private $associations = [
        "UniMapper\Reflection\Entity\Property\Association\HasOne",
        "UniMapper\Reflection\Entity\Property\Association\HasMany",
        "UniMapper\Reflection\Entity\Property\Association\BelongsToOne",
        "UniMapper\Reflection\Entity\Property\Association\BelongsToMany"
    ];

    /** @var boolean */
    protected $writable = true;

    public function __construct($definition, Reflection\Entity $entityReflection)
    {
        $this->rawDefinition = $definition;
        $this->entityReflection = $entityReflection;

        $arguments = preg_split('/\s+/', $definition, null, PREG_SPLIT_NO_EMPTY);

        // read only property
        if ($arguments[0] === "-read") {
            $this->writable = false;
            array_shift($arguments);
        }

        $this->readType($arguments[0]);
        next($arguments);

        $this->readName($arguments[1]);
        next($arguments);

        foreach ($arguments as $argument) {
            $this->readFilters($argument);
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
     * Get property name
     *
     * @return string
     *
     * @throws \UniMapper\Exception\PropertyException
     */
    public function getName()
    {
        if ($this->name === null) {
            throw new Exception\PropertyException(
                "Property name is not set!",
                $this->entityReflection,
                $this->rawDefinition
            );
        }
        return $this->name;
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
     * @throws \UniMapper\Exception\PropertyException
     */
    protected function readName($definition)
    {
        $length = strlen($definition);
        if ($length === 1 || substr($definition, 0, 1) !== "$") {
            throw new Exception\PropertyException(
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
     * @throws \UniMapper\Exception\PropertyException
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    public function getMappedName()
    {
        if ($this->mapping && $this->mapping->getName()) {
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
    protected function readType($definition)
    {
        $basic = implode("|", $this->basicTypes);
        if (preg_match("#^(" . $basic . ")$#", $definition)) {
            // Basic type

            return $this->type = $definition;
        } elseif ($definition === self::TYPE_DATETIME) {
            // DateTime

            return $this->type = $definition;
        } elseif (class_exists(NC::nameToClass($definition, NC::$entityMask))) {
            // Entity

            return $this->type = $this->_loadReflection(
                NC::nameToClass($definition, NC::$entityMask)
            );
        } elseif (preg_match("#(.*?)\[\]#s", $definition)) {
            // Collection

            try {
                $entityReflection = $this->_loadReflection(
                    NC::nameToClass(rtrim($definition, "[]"), NC::$entityMask)
                );
            } catch (Exception\InvalidArgumentException $exception) {

            }

            return $this->type = new EntityCollection($entityReflection);
        }

        throw new Exception\PropertyException(
            "Unsupported type '" . $definition . "'!",
            $this->entityReflection,
            $this->rawDefinition
        );
    }

    /**
     * Load lazy entity reflection
     *
     * @param string $entityClass
     *
     * @return \UniMapper\Reflection\Entity
     */
    private function _loadReflection($entityClass)
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

            if ($this->mapping) {
                throw new Exception\PropertyException(
                    "Can not combine m:computed with m:mapping!",
                    $this->entityReflection,
                    $definition
                );
            }

            $computedMethodName = $this->getComputedMethodName();
            if (!method_exists($this->entityReflection->getClassName(), $computedMethodName)) {
                throw new Exception\PropertyException(
                    "Can not find computed method with name "
                    . $computedMethodName . "!",
                    $this->entityReflection,
                    $definition
                );
            }
            $this->computed = true;
        } elseif (preg_match("#m:map\((.*?)\)#s", $definition, $matches)) {
            // m:map(name='column' filter=in_fnc|out_fnc)

            if ($this->mapping) {
                throw new Exception\PropertyException(
                    "Mapping already defined!",
                    $this->entityReflection,
                    $definition
                );
            }

            if ($this->computed) {
                throw new Exception\PropertyException(
                    "Can not combine m:mapping with m:computed!",
                    $this->entityReflection, $definition
                );
            }

            $this->mapping = new Property\Mapping(
                $matches[1],
                $definition,
                $this->entityReflection
            );
        } elseif (preg_match("#m:enum\(([a-zA-Z0-9]+|self|parent)::([a-zA-Z0-9_]+)\*\)#", $definition, $matches)) {
            // m:enum(self::CUSTOM_*)
            // m:enum(parent::CUSTOM_*)
            // m:enum(MY_CLASS::CUSTOM_*)

            if ($this->computed) {
                throw new Exception\PropertyException(
                    "Can not combine m:computed with m:enum!",
                    $this->entityReflection,
                    $definition
                );
            }
            $this->enumeration = new Property\Enumeration(
                $matches,
                $definition,
                $this->entityReflection
            );
        } elseif (preg_match("#m:primary#s", $definition, $matches)) {
            // m:primary

            if ($this->computed) {
                throw new Exception\PropertyException(
                    "Can not combine m:computed with m:primary!",
                    $this->entityReflection,
                    $definition
                );
            }
            $this->primary = true;
        } elseif (preg_match("#m:assoc\((.*?)\)#s", $definition, $matches)) {
            // m:assoc(....)

            if (!$this->entityReflection->hasAdapter()) {
                throw new Exception\PropertyException(
                    "Can not use associations while entity "
                    . $this->entityReflection->getClassName()
                    . " has no adapter defined!",
                    $this->entityReflection,
                    $definition
                );
            }

            if ($this->computed || $this->mapping || $this->enumeration) {
                throw new Exception\PropertyException(
                    "Association can not be combined with m:map, m:computed or "
                    . "m:enum!",
                    $this->entityReflection,
                    $definition
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
                    $this->entityReflection,
                    $definition
                );
            }
            if (!$targetEntityReflection->hasAdapter()) {
                throw new Exception\PropertyException(
                    "Can not use associations while target entity "
                    . $targetEntityReflection->getClassName()
                    . " has no adapter defined!",
                    $this->entityReflection,
                    $definition
                );
            }

            foreach ($this->associations as $assocClass) {

                try {

                    $this->association = new $assocClass(
                        $this->entityReflection,
                        $targetEntityReflection,
                        $matches[1]
                    );
                } catch (Exception\AssociationParseException $e) {

                    if ($e->getCode() !== Exception\AssociationParseException::INVALID_TYPE) {
                        throw new Exception\PropertyException(
                            $e->getMessage(),
                            $this->entityReflection,
                            $definition
                        );
                    }
                }
            }

            if (!$this->association) {
                throw new Exception\PropertyException(
                    "Unrecognized association m:map(" . $matches[1] . ")!",
                    $this->entityReflection,
                    $definition
                );
            }
        }
    }

    /**
     * Validate value type
     *
     * @param mixed $value Given value
     *
     * @throws \UniMapper\Exception\PropertyValidationException
     * @throws \Exception
     */
    public function validateValueType($value)
    {
        $expectedType = $this->type;

        // Enumeration
        if ($this->enumeration !== null
            && !$this->enumeration->isValueFromEnum($value)
        ) {
            throw new Exception\PropertyValidationException(
                "Value " . $value . " is not from defined entity enumeration "
                . "range on property " . $this->name . "!",
                $this->entityReflection,
                $this->rawDefinition,
                Exception\PropertyValidationException::ENUMERATION
            );
        }

        // Basic type
        if ($this->isTypeBasic()) {

            if (gettype($value) === $expectedType) {
                return;
            }
            throw new Exception\PropertyValidationException(
                "Expected " . $expectedType . " but " . gettype($value)
                . " given on property " . $this->name . "!",
                $this->entityReflection,
                $this->rawDefinition,
                Exception\PropertyValidationException::TYPE
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
                throw new Exception\PropertyValidationException(
                    "Expected entity " . $expectedType . " but " . $givenType
                    . " given on property " . $this->name . "!",
                    $this->entityReflection,
                    $this->rawDefinition,
                    Exception\PropertyValidationException::TYPE
                );
            }

        } elseif ($expectedType instanceof EntityCollection) {
            // Collection

            if (!$value instanceof EntityCollection) {

                throw new Exception\PropertyValidationException(
                    "Expected entity collection but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $this->entityReflection,
                    $this->rawDefinition,
                    Exception\PropertyValidationException::TYPE
                );
            } elseif ($value->getEntityReflection()->getClassName() !== $expectedType->getEntityReflection()->getClassName()) {
                throw new Exception\PropertyValidationException(
                    "Expected collection of entity "
                    . $expectedType->getEntityReflection()->getClassName()
                    . " but collection of entity "
                    . $value->getEntityReflection()->getClassName()
                    . " given on property " . $this->name . "!",
                    $this->entityReflection,
                    $this->rawDefinition,
                    Exception\PropertyValidationException::TYPE
                );
            } else {
                return;
            }

        } elseif ($expectedType === self::TYPE_DATETIME) {
            // DateTime

            if ($value instanceof \DateTime) {
                return;
            } else {
                throw new Exception\PropertyValidationException(
                    "Expected DateTime but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $this->entityReflection,
                    $this->rawDefinition,
                    Exception\PropertyValidationException::TYPE
                );
            }
        }

        throw new \Exception(
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

            $date = $value;
            if (Validator::isTraversable($value)) {
                if (isset($value["date"])) {
                    $date = $value["date"];
                }
            }
            try {
                $date = new \DateTime($date);
            } catch (\Exception $e) {

            }
            if ($date instanceof \DateTime) {
                return $date;
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