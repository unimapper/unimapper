<?php

namespace UniMapper;

use UniMapper\EntityCollection,
    UniMapper\Reflection;

/**
 * Entity is ancestor for all entities and provides global methods, which
 * can be used in every new entity object.
 */
abstract class Entity implements \JsonSerializable, \Serializable, \Iterator
{

    /** @var \UniMapper\Reflection\Entity $reflection */
    private $reflection;

    /** @var array $data Stored variables */
    private $data = [];

    /** @var string $iteration List of property names */
    private $iteration;

    /** @var \UniMapper\Validator $validator */
    protected $validator;

    public function __construct(Reflection\Entity $reflection = null, $values = [])
    {
        if ($reflection) {
            if ($reflection->getClassName() !== get_called_class()) {
                throw new Exception\InvalidArgumentException(
                    "Expected reflection of class '" . get_called_class()
                    . "' but reflection of '" . $reflection->getClassName()
                    . "' given!"
                );
            }
            $this->reflection = $reflection;
        }
        $this->_initialize();
        $this->validator = new Validator($this);

        if ($values) {
            $this->_setValues($values, true);
        }
    }

    private function _setValues($values, $readonlyToo = false)
    {
        if (!Validator::isTraversable($values)) {
            throw new Exception\InvalidArgumentException(
                "Values must be traversable data!"
            );
        }

        foreach ($values as $name => $value) {

            try {
                $this->{$name} = $value;
            } catch (Exception\PropertyException $e) {

                if ($e instanceof Exception\PropertyValidationException
                    && $e->getCode() === Exception\PropertyValidationException::TYPE
                ) {
                    // Try to convert automatically

                    $this->{$name} = $this->reflection->getProperties()[$name]
                        ->convertValue($value);

                } elseif ($e instanceof Exception\PropertyAccessException
                    && $e->getCode() === Exception\PropertyAccessException::READONLY
                    && $readonlyToo
                ) {
                    // Set and convert readonly property automatically

                    $this->data[$name] = $this->reflection->getProperties()[$name]
                        ->convertValue($value);
                }
            }

        }
    }

    /**
     * Initialize entity state
     */
    private function _initialize()
    {
        if (!$this->reflection) {
            $this->reflection = new Reflection\Entity(get_called_class());
        }

        $this->iteration = array_merge(
            array_keys($this->reflection->getProperties()),
            $this->reflection->getPublicProperties()
        );
        $this->rewind();
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
        $this->_initialize();
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
        $this->_setValues($values);
    }

    /**
     * Get property value
     *
     * @param string $name Property name
     *
     * @return mixed
     *
     * @throws Exception\PropertyAccessException
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        $properties = $this->reflection->getProperties();
        if (!isset($properties[$name])) {
            throw new Exception\PropertyAccessException(
                "Undefined property '" . $name . "'!",
                $this->reflection,
                null,
                Exception\PropertyAccessException::UNDEFINED
            );
        }

        // computed property
        if ($properties[$name]->isComputed()) {

            $computedValue = $this->{$properties[$name]->getComputedMethodName()}();
            if ($computedValue === null) {
                return null;
            }
            $properties[$name]->validateValueType($computedValue);
            return $computedValue;
        }

        // empty collection
        $type = $properties[$name]->getType();
        if ($type instanceof EntityCollection) {
            return $type;
        }

        return null;
    }

    /**
     * Set property value
     *
     * @param string $name
     * @param mixed  $value
     *
     * @throws Exception\PropertyAccessException
     */
    public function __set($name, $value)
    {
        $properties = $this->reflection->getProperties();
        if (!isset($properties[$name])) {
            throw new Exception\PropertyAccessException(
                "Undefined property '" . $name . "'!",
                $this->reflection,
                null,
                Exception\PropertyAccessException::UNDEFINED
            );
        }

        if (!$properties[$name]->isWritable()) {
            throw new Exception\PropertyAccessException(
                "Property '" . $name . "' is not writable!",
                $this->reflection,
                $properties[$name]->getRawDefinition(),
                Exception\PropertyAccessException::READONLY
            );
        }

        if ($properties[$name]->isComputed()) {
            throw new Exception\PropertyAccessException(
                "Can not set computed property '" . $name . "'!",
                $this->reflection,
                $properties[$name]->getRawDefinition(),
                Exception\PropertyAccessException::READONLY
            );
        }

        if ($value !== null) {
            $properties[$name]->validateValueType($value);
        }

        // Set value
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    /**
     * Get changed data only
     *
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
        return $this->validator->onEntity();
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
     * Convert to json representation of entity collection
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray(true);
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