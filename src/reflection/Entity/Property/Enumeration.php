<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exception\PropertyException,
    UniMapper\Reflection;

/**
 * Entity property enumeration object
 */
class Enumeration
{

    /** @var array */
    protected $values = array();

    /** @var array */
    protected $index = array();

    public function __construct($definition, $rawDefinition,
        Reflection\Entity $entityReflection
    ) {
        if (empty($definition)) {
            throw new PropertyException(
                "Enumeration definition can not be empty!",
                $entityReflection,
                $rawDefinition
            );
        }

        list(, $class, $prefix) = $definition;
        if ($class === 'self') {
            $constants = $entityReflection->getConstants();
        } elseif ($class === 'parent') {
            $constants = $entityReflection->getParent()->getConstants(); // @todo
        } elseif (class_exists($class)) {
            $aliases = $entityReflection->getAliases();
            $reflectionClass = new \ReflectionClass($aliases->map($class));
            $constants = $reflectionClass->getConstants();
        } else {
            throw new PropertyException(
                "Invalid enumeration definition!",
                $entityReflection,
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
     * Tells whether given value is from enumeration
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