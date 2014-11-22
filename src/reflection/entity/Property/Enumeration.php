<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exception;

/**
 * Entity property enumeration object
 */
class Enumeration
{

    const EXPRESSION = "#m:enum\((.*)::([a-zA-Z0-9_]+)\*\)#";

    /** @var array $values */
    private $values = [];

    /** @var array $index */
    private $index = [];

    public function __construct(array $definition, $entityClass)
    {
        if (empty($definition)) {
            throw new Exception\DefinitionException(
                "Enumeration definition can not be empty!"
            );
        }

        list(, $class, $prefix) = $definition;
        if ($class === 'self') {
            $class = $entityClass;
        }

        if (!class_exists($class)) {
            throw new Exception\DefinitionException(
                "Invalid enumeration definition!"
            );
        }

        $reflectionClass = new \ReflectionClass($class);

        foreach ($reflectionClass->getConstants() as $name => $value) {
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
    public function isValid($value)
    {
        return isset($this->index[$value]);
    }

}