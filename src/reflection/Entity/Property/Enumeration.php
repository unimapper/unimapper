<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exception,
    UniMapper\Reflection;

/**
 * Entity property enumeration object
 */
class Enumeration
{

    const EXPRESSION = "#m:enum\(([a-zA-Z0-9]+)::([a-zA-Z0-9_]+)\*\)#";

    /** @var array $values */
    private $values = [];

    /** @var array $index */
    private $index = [];

    public function __construct(array $definition, Reflection\Entity $entityReflection)
    {
        if (empty($definition)) {
            throw new Exception\DefinitionException(
                "Enumeration definition can not be empty!"
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
            throw new Exception\DefinitionException(
                "Invalid enumeration definition!"
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
     * @param mixed $value
     *
     * @return boolean
     */
    public function isValueFromEnum($value)
    {
        return isset($this->index[$value]);
    }

}