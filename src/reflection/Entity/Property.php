<?php

namespace UniMapper\Reflection\Entity;

use UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection,
    UniMapper\NamingConvention as NC,
    UniMapper\Exception\PropertyValidationException,
    UniMapper\Exception\InvalidArgumentException,
    UniMapper\Exception\PropertyException;

/**
 * Entity property reflection
 */
class Property
{

    /** @var string */
    protected $type;

    /** @var string */
    protected $name;

    /** @var string */
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

    /** @var  array */
    public $customMappers;

    /** @var array */
    private $supportedAssociations = [
        Property\Association\HasOne::TYPE => "HasOne",
        Property\Association\HasMany::TYPE => "HasMany",
        Property\Association\BelongsToOne::TYPE => "BelongsToOne",
        Property\Association\BelongsToMany::TYPE => "BelongsToMany"
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
            throw new PropertyException(
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
     * @throws \UniMapper\Exception\PropertyException
     */
    public function getEnumeration()
    {
        return $this->enumeration;
    }

    public function getMappedName()
    {
        if ($this->mapping !== null) {
            return $this->mapping;
        }
        return $this->name;
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
     * @throws \UniMapper\Exception\PropertyException
     */
    protected function readType($definition)
    {
        $basic = implode("|", $this->basicTypes);
        if (preg_match("#^(" . $basic . ")$#", $definition)) {
            // Basic type

            return $this->type = $definition;
        } elseif ($definition === "DateTime") {
            // DateTime

            return $this->type = $definition;
        } elseif (class_exists(NC::nameToClass($definition, NC::$entityMask))) {
            // Entity

            return $this->type = NC::nameToClass($definition, NC::$entityMask);
        } elseif (preg_match("#(.*?)\[\]#s", $definition)) {
            // Collection

            try {
                return $this->type = new EntityCollection(NC::nameToClass(rtrim($definition, "[]"), NC::$entityMask));
            } catch (InvalidArgumentException $expection) {

            }
        }

        throw new PropertyException("Unsupported type '" . $definition . "'!", $this->entityReflection, $this->rawDefinition);
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
            // m:map(column)

            if ($this->computed) {
                throw new PropertyException("Can not combine m:computed with m:map!", $this->entityReflection, $definition);
            }
            if ($matches[1]) {
                $this->mapping = $matches[1];
            }
        } elseif (preg_match("#m:map-(.*?)\((.*?)\)#s", $definition, $matches)) {
            // m:map-TYPE(customMapperName)

            if ($this->computed) {
                throw new PropertyException("Can not combine m:map- with m:computed!", $this->entityReflection, $definition);
            }

            if ($matches[1]) {
                if ($matches[1] === 'name') {
                    if ($matches[2]){
                        $this->mapping = $matches[2];
                    }
                } else {
                    $this->customMappers[$matches[1]] = isset($matches[2]) ? explode(',', $matches[2]) : [];
                }
            }

        }
        elseif (preg_match("#m:enum\(([a-zA-Z0-9]+|self|parent)::([a-zA-Z0-9_]+)\*\)#", $definition, $matches)) {
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
        } elseif (preg_match("#m:assoc\((.*?)\)#s", $definition, $matches)) {
            // m:assoc(1:1=key|targetKey)

            if ($this->computed || $this->mapping || $this->enumeration) {
                throw new PropertyException("Association can not be combined with mapping, computed or enumeration!", $this->entityReflection, $definition);
            }

            // Get target entity class
            if ($this->type instanceof EntityCollection) {
                $targetEntityClass = $this->type->getEntityClass();
            } elseif (is_subclass_of($this->type, "UniMapper\Entity")) {
                $targetEntityClass = $this->type;
            } else {
                throw new PropertyException("Property type must be collection or entity if association defined!", $this->entityReflection, $definition);
            }

            if (!strpos($matches[1], "=")) {
                throw new PropertyException("Bad association definition!", $this->entityReflection, $definition);
            }
            list($assocType, $parameters) = explode("=", $matches[1]);
            if (!isset($this->supportedAssociations[$assocType])) {
                throw new PropertyException("Association type '" . $assocType . "' not supported!", $this->entityReflection, $definition);
            }
            $assocClass = "UniMapper\Reflection\Entity\Property\Association\\" . $this->supportedAssociations[$assocType];

            $this->association = new $assocClass($this->entityReflection, new Reflection\Entity($targetEntityClass), $parameters);
        }
    }

    /**
     * Validate property value type
     *
     * @param mixed $value Given value
     *
     * @throws \UniMapper\Exception\PropertyValidationException
     * @throws \Exception
     */
    public function validateValue($value)
    {
        $expectedType = $this->type;

        // Enumeration
        if ($this->enumeration !== null && !$this->enumeration->isValueFromEnum($value)) {
            throw new PropertyValidationException(
                "Value " . $value . " is not from defined entity enumeration range on property " . $this->name . "!",
                $this->entityReflection,
                $this->rawDefinition,
                PropertyValidationException::ENUMERATION
            );
        }

        // Basic type
        if ($this->isTypeBasic()) {

            if (gettype($value) === $expectedType) {
                return;
            }
            throw new PropertyValidationException(
                "Expected " . $expectedType . " but " . gettype($value) . " given on property " . $this->name . "!",
                $this->entityReflection,
                $this->rawDefinition,
                PropertyValidationException::TYPE
            );
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
            throw new PropertyValidationException(
                "Expected " . $expectedType . " but " . $givenType . " given on property " . $this->name . "!",
                $this->entityReflection,
                $this->rawDefinition,
                PropertyValidationException::TYPE
            );
        }

        $givenType = gettype($value);
        if ($givenType === "object") {
            $givenType = get_class($value);
        }
        throw new \Exception("Expected " . $expectedType . " but " . $givenType . " given on property " . $this->name . ". It could be an internal ORM error!");
    }

    /**
     * Try to convert value on required type automatically
     *
     * @param mixed $value
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
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
        } elseif ($this->type === "DateTime") {
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
        } elseif ($this->type instanceof EntityCollection && Validator::isTraversable($value)) {
            // Collection

            $reflection = new Reflection\Entity($this->type->getEntityClass()); // @todo better reflection giving
            $collection = new EntityCollection($reflection->getClassName());
            foreach ($value as $index => $data) {
                $collection[$index] = $reflection->createEntity($data);
            }
            return $collection;
        } elseif (is_subclass_of($this->type, "UniMapper\Entity") && Validator::isTraversable($value)) {
            // Entity

            $reflection = new Reflection\Entity($this->type); // @todo better reflection giving
            return $reflection->createEntity($value);
        }

        throw new InvalidArgumentException("Can not convert value on property '" . $this->name . "' automatically!");
    }

    public function isTypeBasic()
    {
        return in_array($this->type, $this->getBasicTypes());
    }

    public function isTypeEntity()
    {
        return is_subclass_of($this->type, "UniMapper\Entity");
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

    /**
     * @return array
     */
    public function hasCustomMappers()
    {
        return $this->customMappers !== null && $this->customMappers;
    }

    /**
     * @param string $name
     *
     * @return bool true if exists
     */
    public function hasCustomMapper($name)
    {
        return isset($this->customMappers[$name]);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getCustomMapper($name)
    {
        return $this->customMappers[$name];
    }

}
