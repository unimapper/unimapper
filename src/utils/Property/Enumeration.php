<?php

namespace UniMapper\Utils\Property;

use UniMapper\Exceptions\PropertyException;

/**
 * Entity property enumeration object
 */
class Enumeration
{

    /** @var array */
    protected $values = array();

    /** @var array */
    protected $index = array();

    /**
     * Constructor
     *
     * @param string           $definition    Enumeration definition
     * @param string           $rawDefinition Raw property definition
     * @param \ReflectionClass $reflection    Entity reflection
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function __construct($definition, $rawDefinition, \ReflectionClass $reflection)
    {
        if (empty($definition)) {
            throw new PropertyException(
                "Enumeration definition can not be empty!",
                $reflection,
                $rawDefinition
            );
        }

        list(, $class, $prefix) = $definition;
        if ($class === 'self') {
            $constants = $reflection->getConstants();
        } elseif ($class === 'parent') {
            $constants = $reflection->getParentClass()->getConstants();
        } elseif (class_exists($class)) {
            $aliases = $reflection->getAliases();
            $reflectionClass = new \ReflectionClass($aliases->map($class));
            $constants = $reflectionClass->getConstants();
        } else {
            throw new PropertyException(
                "Invalid enumeration definition!",
                $reflection,
                $rawDefinition
            );
        }

        foreach ($constants as $name => $value) {
            if (substr($name, 0, strlen($prefix)) === $prefix) {
                $this->values[$name] = $value;
                $this->index[$value] = true;
            }
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    /**
     * Tells wheter given value is from enumeration
     *
     * @param mixed $value Value
     *
     * @return boolean
     */
    public function isValueFromEnum($value)
    {
        return isset($this->index[$value]);
    }

}