<?php

namespace UniMapper;

use UniMapper\EntityCollection,
    UniMapper\Reflection;

abstract class Entity implements \JsonSerializable, \Serializable, \Iterator
{

    public static $dateFormat = "Y-m-d";

    const CHANGE_ATTACH = 1;
    const CHANGE_DETACH = 2;
    const CHANGE_ADD = 3;
    const CHANGE_REMOVE = 4;

    /** @var \UniMapper\Reflection\Entity $reflection */
    private $reflection;

    /** @var array $data Stored variables */
    private $data = [];

    /** @var string $iteration List of property names */
    private $iteration;

    /** @var \UniMapper\Validator $validator */
    private $validator;

    /** @var array $changes Properties with changes */
    private $changes = [];

    /** @var integer $change */
    private $changeType;

    /**
     * @param mixed $values
     */
    public function __construct($values = null)
    {
        $this->reflection = Reflection\Loader::load(get_called_class());

        if ($values) {
            $this->_setProperties($values, true, true, true, true);
        }

        $this->_resetIterator();
    }

    private function _setProperties(
        $values,
        $autoConvert = true,
        $skipUndefined = false,
        $setReadonly = false,
        $skipUnwritable = false
    ) {
        if (!Validator::isTraversable($values)) {
            throw new Exception\InvalidArgumentException(
                "Values must be traversable data!",
                $values
            );
        }

        foreach ($values as $name => $value) {

            // Public
            if (in_array($name, $this->reflection->getPublicProperties())) {
                $this->{$name} = $value;
                continue;
            }

            // Undefined
            if (!$this->reflection->hasProperty($name)) {

                if ($skipUndefined) {
                    continue;
                }

                throw new Exception\InvalidArgumentException(
                    "Undefined property '" . $name . "'!"
                );
            }

            $property = $this->reflection->getProperty($name);

            // Computed
            if ($property->hasOption(Reflection\Property::OPTION_COMPUTED)) {

                if ($skipUnwritable) {
                    continue;
                }

                throw new Exception\InvalidArgumentException(
                    "Computed property is read-only!"
                );
            }

            // Readonly
            if (!$property->isWritable() && !$setReadonly) {

                if ($skipUnwritable) {
                    continue;
                }

                throw new Exception\InvalidArgumentException(
                    "Property '" . $name . "' is read-only!"
                );
            }

            // Validate type
            try {
                $property->validateValueType($value);
            } catch (Exception\InvalidArgumentException $e) {

                if ($autoConvert) {
                    $value = $property->convertValue($value);
                } else {
                    throw $e;
                }
            }

            // Set value
            $this->data[$name] = $value;
        }
    }

    /**
     * Reset iterator
     */
    private function _resetIterator()
    {
        if (!$this->reflection) {
            $this->reflection = Reflection\Loader::load(get_called_class());
        }

        $this->iteration = array_merge(
            array_keys($this->reflection->getProperties()),
            $this->reflection->getPublicProperties()
        );
        $this->rewind();
    }

    private function _validateChangeType()
    {
        if (!$this->reflection->hasPrimary()) {
            throw new Exception\InvalidArgumentException(
                "Only entity with primary can define changes!"
            );
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        if (empty($this->{$primaryName})) {
            throw new Exception\InvalidArgumentException(
                "Primary value can not be empty!"
            );
        }
    }

    public function attach()
    {
        $this->_validateChangeType();
        $this->changeType = self::CHANGE_ATTACH;
    }

    public function detach()
    {
        $this->_validateChangeType();
        $this->changeType = self::CHANGE_DETACH;
    }

    public function add()
    {
        $this->_validateChangeType();
        $this->changeType = self::CHANGE_ADD;
    }

    public function remove()
    {
        $this->_validateChangeType();
        $this->changeType = self::CHANGE_REMOVE;
    }

    /**
     * Serialize entity data and public properties
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(
            array_merge($this->data, $this->_getPublicPropertyValues())
        );
    }

    public function unserialize($data)
    {
        $this->_resetIterator();
        foreach (unserialize($data) as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Import and try to convert values automatically if possible, skip readonly
     * and undefined.
     *
     * @param mixed $values Traversable structure (array/object)
     */
    public function import($values)
    {
        $this->_setProperties($values, true, true, false, true);
    }

    /**
     * Manage entity and collection changes on target property
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return Entity|EntityCollection
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        if (!$this->reflection->hasProperty($name)) {
            throw new Exception\InvalidArgumentException(
                "Undefined property '" . $name . "'!"
            );
        }

        $propertyReflection = $this->reflection->getProperty($name);

        if ($propertyReflection->getType() !== Reflection\Property::TYPE_ENTITY
            && $propertyReflection->getType() !== Reflection\Property::TYPE_COLLECTION
        ) {
            throw new Exception\InvalidArgumentException(
                "Only properties with type entity or collection can call changes!"
            );
        }

        if (isset($arguments[0])) {

            if ($arguments[0] === false) {
                unset($this->changes[$name]);
            } else {

                if (!$arguments[0] instanceof EntityCollection
                    && $propertyReflection->getType() === Reflection\Property::TYPE_COLLECTION
                ) {
                    throw new Exception\InvalidArgumentException(
                        "You must pass instance of entity collection!",
                        $arguments[0]
                    );
                }

                if (!$arguments[0] instanceof Entity
                    && $propertyReflection->getType() === Reflection\Property::TYPE_ENTITY
                ) {
                    throw new Exception\InvalidArgumentException(
                        "You must pass instance of entity!",
                        $arguments[0]
                    );
                }

                $this->changes[$name] = $arguments[0];
            }
        }

        if (!isset($this->changes[$name])) {

            if ($propertyReflection->getType() === Reflection\Property::TYPE_COLLECTION) {
                $this->changes[$name] = new EntityCollection($propertyReflection->getTypeOption());
            } else {
                $this->changes[$name] = Reflection\Loader::load($propertyReflection->getTypeOption())->createEntity();
            }
        }

        return $this->changes[$name];
    }

    /**
     * Get property value
     *
     * @param string $name Property name
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        if (!$this->reflection->hasProperty($name)) {
            throw new Exception\InvalidArgumentException(
                "Undefined property '" . $name . "'!"
            );
        }

        $property = $this->reflection->getProperty($name);

        // computed property
        if ($property->hasOption(Reflection\Property::OPTION_COMPUTED)) {

            $computedValue = $this->{$property->getOption(Reflection\Property::OPTION_COMPUTED)}();
            if ($computedValue === null) {
                return null;
            }
            $property->validateValueType($computedValue);
            return $computedValue;
        }

        // empty collection
        if ($property->getType() === Reflection\Property::TYPE_COLLECTION) {
            return $this->data[$name] = new EntityCollection($property->getTypeOption());
        }

        return null;
    }

    /**
     * Set property value
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->_setProperties([$name => $value], false);
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function getChangeType()
    {
        return $this->changeType;
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get entity validator
     *
     * @return \UniMapper\Validator
     */
    public function getValidator()
    {
        if (!$this->validator) {
            $this->validator = new Validator($this);
        }
        return $this->validator->onEntity();
    }

    /**
     * Query on entity
     *
     * @return QueryBuilder
     */
    public static function query()
    {
        return new QueryBuilder(get_called_class());
    }

    /**
     * Get entity values as array
     *
     * @param boolean $nesting Convert nested entities and collections too
     *
     * @return array
     */
    public function toArray($nesting = false)
    {
        $output = array();
        foreach ($this->reflection->getProperties() as $propertyName => $property) {

            $value = $this->{$propertyName};
            if (($value instanceof EntityCollection || $value instanceof Entity)
                && $nesting
            ) {
                $output[$propertyName] = $value->toArray($nesting);
            } else {
                $output[$propertyName] = $value;
            }
        }

        return array_merge($output, $this->_getPublicPropertyValues());
    }

    private function _getPublicPropertyValues()
    {
        $result = [];
        foreach ($this->reflection->getPublicProperties() as $name) {
            $result[$name] = $this->{$name};
        }
        return $result;
    }

    /**
     * Gets data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $output = [];
        foreach ($this->reflection->getProperties() as $propertyName => $property) {

            $value = $this->{$propertyName};
            if ($value instanceof EntityCollection || $value instanceof Entity) {
                $output[$propertyName] = $value->jsonSerialize();
            } elseif ($value instanceof \DateTime
                && $property->getType() === Reflection\Property::TYPE_DATE
            ) {
                $output[$propertyName] = (array) $value;
                $output[$propertyName]["date"] = $value->format(self::$dateFormat);
            } else {
                $output[$propertyName] = $value;
            }
        }

        return array_merge($output, $this->_getPublicPropertyValues());
    }

    public function rewind()
    {
        reset($this->iteration);
    }

    public function current()
    {
        return $this->{$this->key()};
    }

    public function key()
    {
        return current($this->iteration);
    }

    public function next()
    {
        next($this->iteration);
    }

    public function valid()
    {
        return key($this->iteration) !== null;
    }

}