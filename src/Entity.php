<?php

namespace UniMapper;

use UniMapper\EntityCollection,
    UniMapper\Reflection;

abstract class Entity implements \JsonSerializable, \Serializable, \Iterator
{

    /** @var \UniMapper\Reflection\Entity $reflection */
    private $reflection;

    /** @var array $data Stored variables */
    private $data = [];

    /** @var string $iteration List of property names */
    private $iteration;

    /** @var \UniMapper\Validator $validator */
    private $validator;

    /** @var array $modifiers */
    private $modifiers = [];

    /**
     * @param mixed $values
     */
    public function __construct($values = null)
    {
        $this->reflection = Reflection\Loader::load(get_called_class());

        if ($values) {
            $this->_setValues($values, true);
        }

        $this->_resetIterator();
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

                if ($e instanceof Exception\PropertyValueException
                    && $e->getCode() === Exception\PropertyValueException::TYPE
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
        $this->_setValues($values);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return Modifier
     *
     * @throws Exception\PropertyAccessException
     */
    public function __call($name, $arguments)
    {
        if (!$this->reflection->hasProperty($name)) {
            throw new Exception\PropertyAccessException(
                "Undefined property '" . $name . "'!",
                $this->reflection,
                null,
                Exception\PropertyAccessException::UNDEFINED
            );
        }

        $propertyReflection = $this->reflection->getProperty($name);
        if (!$propertyReflection->hasOption(Reflection\Property::OPTION_ASSOC)) {
            throw new Exception\PropertyAccessException(
                "Only association properties can be called as function!",
                $this->reflection
            );
        }

        if (!in_array($name, $this->modifiers, true)) {

            if ($this->reflection->getProperty($name)->getType() === Reflection\Property::TYPE_COLLECTION) {
                $this->modifiers[$name] = new Modifier\CollectionModifier(
                    $this->reflection->getProperty($name)->getOption(Reflection\Property::OPTION_ASSOC)
                );
            } elseif ($this->reflection->getProperty($name)->getType() === Reflection\Property::TYPE_ENTITY) {
                $this->modifiers[$name] = new Modifier\EntityModifier(
                    $this->reflection->getProperty($name)->getOption(Reflection\Property::OPTION_ASSOC)
                );
            }
        }

        return $this->modifiers[$name];
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
        if ($properties[$name]->hasOption(Reflection\Property::OPTION_COMPUTED)) {

            $computedValue = $this->{$properties[$name]->getOption(Reflection\Property::OPTION_COMPUTED)}();
            if ($computedValue === null) {
                return null;
            }
            $properties[$name]->validateValueType($computedValue);
            return $computedValue;
        }

        // empty collection
        if ($properties[$name]->getType() === Reflection\Property::TYPE_COLLECTION) {
            return $this->data[$name] = new EntityCollection($properties[$name]->getTypeOption());
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
                "Property '" . $name . "' is read-only!",
                $this->reflection,
                null,
                Exception\PropertyAccessException::READONLY
            );
        }

        if ($properties[$name]->hasOption(Reflection\Property::OPTION_COMPUTED)) {
            throw new Exception\PropertyAccessException(
                "Computed property is read-only!",
                $this->reflection,
                null,
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

    public function getModifiers()
    {
        return $this->modifiers;
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
        if (!$this->validator) {
            $this->validator = new Validator($this);
        }
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