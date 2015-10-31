<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Exception\OptionException;

class Enum implements Reflection\Property\IOption
{

    const KEY = "enum";

    /** @var array $values */
    private $values = [];

    /** @var array $index */
    private $index = [];

    public function __construct(\ReflectionClass $reflectionClass, $prefix)
    {
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

    public static function create(
        Reflection\Property $property,
        $value = null,
        array $parameters = []
    ) {
        if (empty($value)) {
            throw new OptionException("Enumeration value must be defined!");
        }

        if (!preg_match('/^\s*(\S+)::(\S*)\*\s*$/', $value, $matched)) {
            throw new OptionException(
                "Invalid enumeration definition!"
            );
        }

        // Find out enumeration class
        if ($matched[1] === 'self') {
            $matched[1] = $property->getEntityReflection()->getClassName();
        }

        if (!class_exists($matched[1])) {
            throw new OptionException(
                "Enumeration class " . $matched[1] . " not found!"
            );
        }

        return new self(new \ReflectionClass($matched[1]), $matched[2]);
    }

    public static function afterCreate(Reflection\Property $property, $option)
    {}

}