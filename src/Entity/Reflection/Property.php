<?php

namespace UniMapper\Entity\Reflection;

use UniMapper\Validator,
    UniMapper\NamingConvention as UNC,
    UniMapper\Exception;
use UniMapper\Entity;

class Property implements \JsonSerializable
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

    const OPTION_ASSOC = "assoc",
          OPTION_ASSOC_BY = "assoc-by",
          OPTION_COMPUTED = "computed",
          OPTION_ENUM = "enum",
          OPTION_MAP = "map",
          OPTION_MAP_BY = "map-by",
          OPTION_MAP_FILTER = "map-filter",
          OPTION_PRIMARY = "primary";

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

    /** @var array $assocTypes List of available association types */
    private $assocTypes = [
        "M:N" => "ManyToMany",
        "M<N" => "ManyToMany",
        "M>N" => "ManyToMany",
        "N:1" => "ManyToOne",
        "1:1" => "OneToOne",
        "1:N" => "OneToMany"
    ];

    /** @var boolean $readonly */
    private $readonly = false;

    /** @var array */
    private $options = [];

    /** @var array */
    private static $assocFilters = [];

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
        $this->options = Annotation::parseOptions($options);
        $this->readonly = (bool) $readonly;

        $this->_initType($type);
        $this->_initComputed();
        $this->_initMapping();
        $this->_initEnumeration();
        $this->_initAssociation();
    }

    public static function registerAssocFilter($name, callable $callback)
    {
        self::$assocFilters[$name] = $callback;
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
        if ($unmapped && $this->hasOption(self::OPTION_MAP_BY)) {
            return $this->getOption(self::OPTION_MAP_BY);
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

        // Validate primary type
        $requiredPrimaryType = [
            Property::TYPE_DOUBLE,
            Property::TYPE_INTEGER,
            Property::TYPE_STRING
        ];
        if ($this->hasOption(self::OPTION_PRIMARY)
            && !in_array($this->type, $requiredPrimaryType, true)
        ) {
            throw new Exception\PropertyException(
                "Primary property can be only "
                . implode(",", $requiredPrimaryType) . " but '" . $this->type
                . "' given!"
            );
        }
    }

    private function _initMapping()
    {
        if ($this->hasOption(self::OPTION_MAP)) {

            // Mapping disabled
            if ($this->getOption(self::OPTION_MAP) === "false") {
                return;
            }
        }

        // Init filter
        if ($this->hasOption(self::OPTION_MAP_FILTER)) {

            $filter = explode("|", $this->getOption(self::OPTION_MAP_FILTER));
            if (!isset($filter[0]) || !isset($filter[1])) {
                throw new Exception\PropertyException("You must define input/output filter!");
            }

            $filterIn = $this->_createCallback($this->entityReflection->getClassName(), $filter[0]);
            if (!$filterIn) {
                throw new Exception\PropertyException("Invalid input filter definition!");
            }

            $filterOut = $this->_createCallback($this->entityReflection->getClassName(), $filter[1]);
            if (!$filterOut) {
                throw new Exception\PropertyException("Invalid output filter definition!");
            }

            $this->options[self::OPTION_MAP_FILTER] = [$filterIn, $filterOut];
        }
    }

    private function _initEnumeration()
    {
        if ($this->hasOption(self::OPTION_ENUM)) {

            if (!preg_match("/^\s*(\S+)::(\S*)\*\s*$/", $this->getOption(self::OPTION_ENUM), $matched)) {
                throw new Exception\PropertyException(
                    "Invalid enumeration definition!"
                );
            }

            // Find out enumeration class
            if ($matched[1] === 'self') {
                $class = $this->entityReflection->getClassName();
            } else {

                $class = $matched[1];
                if (!class_exists($class)) {
                    throw new Exception\PropertyException(
                        "Enumeration class " . $class . " not found!"
                    );
                }
            }

            $this->options[self::OPTION_ENUM] = new Enumeration($class, $matched[2]);
        }
    }

    private function _initAssociation()
    {
        if ($this->hasOption(self::OPTION_ASSOC)) {

            if ($this->hasOption(self::OPTION_MAP)
                || $this->hasOption(self::OPTION_ENUM)
                || $this->hasOption(self::OPTION_COMPUTED)
            ) {
                throw new Exception\PropertyException(
                    "Association can not be combined with mapping, computed or "
                    . "enumeration!"
                );
            }

            if ($this->type !== self::TYPE_COLLECTION
                && $this->type !== self::TYPE_ENTITY
            ) {
                throw new Exception\PropertyException(
                    "Property type must be collection or entity if association "
                    . "defined!"
                );
            }

            if (!$this->hasOption(self::OPTION_ASSOC)) {
                throw new Exception\PropertyException(
                    "You must define association type!"
                );
            }

            if (!$this->hasOption(self::OPTION_ASSOC_BY)) {
                throw new Exception\PropertyException(
                    "You must define association by!"
                );
            }

            $class = "UniMapper\Association\\"
                . $this->assocTypes[$this->getOption(self::OPTION_ASSOC)];

            try {

                $association = new $class(
                    $this->name,
                    $this->entityReflection,
                    Entity\Reflection::load($this->typeOption),
                    explode("|", $this->getOption(self::OPTION_ASSOC_BY)),
                    $this->getOption(self::OPTION_ASSOC) === "M<N" ? false : true
                );
            } catch (Exception\AssociationException $e) {
                throw new Exception\PropertyException($e->getMessage());
            }

            // Get filter
            $filters = preg_grep("/" . self::OPTION_ASSOC . "-filter-[aA-zZ]*/", $this->options);
            if ($filters) {

                if (count($filters) > 1) {
                    throw new Exception\PropertyException(
                        "Only one association filter can be set!"
                    );
                }

                $name = end(explode("-", key($filters)));
                if (isset(self::$assocFilters[$name])) {

                    $args = current($filters);
                    array_unshift($args, $association);

                    // Apply filter on association
                    call_user_func_array(self::$assocFilters[$name], $args);
                } else {
                    throw new Exception\PropertyException(
                        "Association filter " . $name . " not is registered!"
                    );
                }
            }

            $this->options[self::OPTION_ASSOC] = $association;
        }
    }

    private function _initComputed()
    {
        if ($this->hasOption(self::OPTION_COMPUTED)) {

            if ($this->hasOption(self::OPTION_MAP)
                || $this->hasOption(self::OPTION_ENUM)
                || $this->hasOption(self::OPTION_PRIMARY)
            ) {
                throw new Exception\PropertyException(
                    "Computed property can not be combined with mapping, "
                    . "enumeration or primary!"
                );
            }

            $method = "compute" . ucfirst($this->name);
            if (!method_exists($this->entityReflection->getClassName(), $method)) {
                throw new Exception\PropertyException(
                    "Computed method " . $method . " not found in "
                    . $this->entityReflection->getClassName() . "!"
                );
            }
            $this->options[self::OPTION_COMPUTED] = $method;
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
        if ($this->hasOption(self::OPTION_PRIMARY)
            && ($value === null || $value === "")
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
        if ($this->hasOption(self::OPTION_ENUM) && !$this->getOption(self::OPTION_ENUM)->isValid($value)) {
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

    private function _createCallback($class, $method)
    {
        if (method_exists($class, $method)) {
            return [$class, $method];
        } elseif (is_callable($method)) {
            return $method;
        }

        return false;
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

    public function jsonSerialize()
    {
        return [
            "name" => $this->name,
            "readonly" => $this->readonly,
            "type" => $this->type,
            "typeOption" => $this->typeOption,
            "options" => $this->options
        ];
    }

}