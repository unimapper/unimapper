<?php

namespace UniMapper\Entity\Reflection;

use UniMapper\Validator,
    UniMapper\NamingConvention as UNC,
    UniMapper\Exception;
use UniMapper\Entity;

class Property
{

    const TYPE_DATETIME = "DateTime",
          TYPE_DATE = "Date",
          TYPE_COLLECTION = "collection",
          TYPE_ENTITY = "entity",
          TYPE_BOOLEAN = "boolean",
          TYPE_INTEGER = "integer",
          TYPE_DOUBLE = "double",
          TYPE_STRING = "string",
          TYPE_ARRAY = "array";

    /** @var string $type */
    private $type;

    /** @var mixed $typeOption */
    private $typeOption;

    /** @var string $name */
    private $name;

    /** @var array $scalarTypes */
    private static $scalarTypes = [
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_DOUBLE,
        self::TYPE_STRING
    ];

    /** @var array $typeAliases */
    private static $typeAliases = [
        "bool" => self::TYPE_BOOLEAN,
        "int" =>  self::TYPE_INTEGER,
        "real" => self::TYPE_DOUBLE,
        "float" => self::TYPE_DOUBLE
    ];

    /** @var Entity\Reflection */
    private $entityReflection;

    /** @var boolean $readonly */
    private $readonly = false;

    /** @var array */
    private $options = [];

    /**
     * @param string            $type
     * @param string            $name
     * @param Entity\Reflection $entityReflection
     * @param bool              $readonly
     * @param string            $options
     */
    public function __construct(
        $type,
        $name,
        Entity\Reflection $entityReflection,
        $readonly = false,
        $options = null
    ) {
        $this->entityReflection = $entityReflection;
        $this->name = $name;
        $this->readonly = (bool) $readonly;
        $this->_initType($type);
        $this->options = $this->_initOptions($options);
    }

    private function _initOptions($options)
    {
        $parsed = Annotation::parseOptions($options);

        $result = [];
        foreach (Annotation::getRegisteredOptions() as $key => $class) {

            $value = array_key_exists($key, $parsed) ? $parsed[$key] : false;
            $parameters = preg_grep("/" . $key . "-[aA-zZ-]*/", array_keys($parsed));
            if ($value !== false || !empty($parameters)) {

                try {

                    $result[$key] = $class::create(
                        $this,
                        $value,
                        array_intersect_key($parsed,  array_flip($parameters))
                    );
                 } catch (Exception\OptionException $e) {

                    throw new Exception\PropertyException(
                        $e->getMessage(),
                        $e->getCode(),
                        $e
                    );
                }
            }
        }
        return $result;
    }

    public function isWritable()
    {
        return $this->readonly;
    }

    /**
     * Is type scalar?
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isScalarType($type)
    {
        return in_array($type, self::$scalarTypes, true);
    }

    /**
     * Get entity reflection
     *
     * @return Entity\Reflection
     */
    public function getEntityReflection()
    {
        return $this->entityReflection;
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
        if ($unmapped && $this->hasOption(Entity\Reflection\Property\Option\Map::KEY)) {
            return $this->getOption(Entity\Reflection\Property\Option\Map::KEY)->getUnmapped();
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

    public function getTypeOption()
    {
        return $this->typeOption;
    }

    /**
     * Initialize property type
     *
     * @param string $definition
     *
     * @return mixed
     *
     * @throws Exception\PropertyException
     */
    private function _initType($definition)
    {
        if (isset(self::$typeAliases[$definition])) {
            $definition = self::$typeAliases[$definition];
        }

        if (self::isScalarType($definition)
            || in_array(
                $definition,
                [self::TYPE_ARRAY, self::TYPE_DATE, self::TYPE_DATETIME],
                true
            )
        ) {
            // Scalar, array, date, datetime

            $this->type = $definition;
        } elseif (class_exists(UNC::nameToClass($definition, UNC::ENTITY_MASK))) {
            // Entity

            $this->type = self::TYPE_ENTITY;
            $this->typeOption = $definition;
        } elseif (substr($definition, -2) === "[]") {
            // Collection

            $this->type = self::TYPE_COLLECTION;
            $this->typeOption = rtrim($definition, "[]");
        } else {
            throw new Exception\PropertyException(
                "Unsupported type '" . $definition . "'!"
            );
        }
    }

    /**
     * Validate value type
     *
     * @param mixed $value Given value
     *
     * @throws Exception\InvalidArgumentException
     * @throws \Exception
     */
    public function validateValueType($value)
    {
        if ($this->hasOption(Entity\Reflection\Property\Option\Primary::KEY)
            && (Entity\Reflection\Property\Option\Primary::isEmpty($value))
        ) {
            throw new Exception\InvalidArgumentException(
                "Primary value can not be empty string or null!",
                $value
            );
        }

        if ($value === null) {
            return;
        }

        // Enumeration
        if ($this->hasOption(Entity\Reflection\Property\Option\Enum::KEY)
            && !$this->getOption(Entity\Reflection\Property\Option\Enum::KEY)->isValid($value)
        ) {
            throw new Exception\InvalidArgumentException(
                "Value " . $value . " is not from defined entity enumeration "
                . "range on property " . $this->name . "!",
                $value
            );
        }

        // Scalar or array type
        if (self::isScalarType($this->type) || $this->type === self::TYPE_ARRAY) {

            if (gettype($value) === $this->type) {
                return;
            }
            throw new Exception\InvalidArgumentException(
                "Expected " . $this->type . " but " . gettype($value)
                . " given on property " . $this->name . "!",
                $value
            );
        }

        // Object validation
        $givenType = gettype($value);
        if ($givenType === "object") {
            $givenType = get_class($value);
        }

        if ($this->type === self::TYPE_ENTITY) {
            // Entity

            $expectedEntityClass = UNC::nameToClass($this->typeOption, UNC::ENTITY_MASK);
            if ($value instanceof $expectedEntityClass) {
                return;
            } else {
                throw new Exception\InvalidArgumentException(
                    "Expected entity " . $this->typeOption . " but " . $givenType
                    . " given on property " . $this->name . "!",
                    $value
                );
            }
        } elseif ($this->type === self::TYPE_COLLECTION) {
            // Collection

            $entityClass = UNC::nameToClass($this->typeOption, UNC::ENTITY_MASK);
            if (!$value instanceof Entity\Collection) {

                throw new Exception\InvalidArgumentException(
                    "Expected entity collection but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $value
                );
            } elseif ($value->getEntityClass() !== $entityClass) {
                throw new Exception\InvalidArgumentException(
                    "Expected collection of entity " . $entityClass
                    . " but collection of entity " . $value->getEntityClass()
                    . " given on property " . $this->name . "!",
                    $value
                );
            } else {
                return;
            }
        } elseif ($this->type === self::TYPE_DATETIME) {
            // DateTime

            if ($value instanceof \DateTime) {
                return;
            } else {
                throw new Exception\InvalidArgumentException(
                    "Expected DateTime but " . $givenType . " given on"
                    . " property " . $this->name . "!",
                    $value
                );
            }
        } elseif ($this->type === self::TYPE_DATE) {
            // Date

            if ($value instanceof \DateTime) {
                return;
            } else {
                throw new Exception\InvalidArgumentException(
                    "Expected date as DateTime object but " . $givenType
                    . " given on property " . $this->name . "!",
                    $value
                );
            }
        } else {
            // Unexpected

            throw new \Exception("Unsupported type " . $this->type . "!");
        }
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
        if ($value === null || ($value === "" && $this->type !== self::TYPE_STRING)) {
            return;
        }

        if (self::isScalarType($this->type) || $this->type === self::TYPE_ARRAY) {
            // Scalar and array

            if ($this->type === self::TYPE_BOOLEAN && strtolower($value) === "false") {
                return false;
            }

            if (is_scalar($value) || $this->type === self::TYPE_ARRAY) {
                if (settype($value, $this->type)) {
                    return $value;
                }
            }
        } elseif ($this->type === self::TYPE_DATETIME
            || $this->type === self::TYPE_DATE
        ) {
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
        } elseif ($this->type === self::TYPE_COLLECTION
            && Validator::isTraversable($value)
        ) {
            // Collection

            return new Entity\Collection($this->typeOption, $value);
        } elseif ($this->type === self::TYPE_ENTITY
            && Validator::isTraversable($value)
        ) {
            // Entity

            return Entity\Reflection::load($this->typeOption)->createEntity($value);
        }

        throw new Exception\InvalidArgumentException(
            "Can not convert value on property '" . $this->name
            . "' automatically!",
            $value
        );
    }

    /**
     * Has option?
     *
     * @param string $key
     *
     * @return boolean
     */
    public function hasOption($key)
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Get option
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     */
    public function getOption($key)
    {
        if (!$this->hasOption($key)) {
            throw new Exception\InvalidArgumentException(
                "Option " . $key . " not defined on "
                . $this->entityReflection->getClassName() . "::$"
                . $this->name . "!"
            );
        }
        return $this->options[$key];
    }

}