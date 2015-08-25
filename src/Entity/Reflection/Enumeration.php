<?php

namespace UniMapper\Entity\Reflection;

class Enumeration implements \JsonSerializable
{

    /** @var array $values */
    private $values = [];

    /** @var array $index */
    private $index = [];

    /** @var string */
    private $class;

    /** @var string */
    private $prefix;

    public function __construct($class, $prefix)
    {
        $this->class = $class;
        $this->prefix = $prefix;

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

    public function jsonSerialize()
    {
        return [
            "class" => $this->class,
            "prefix" => $this->prefix
        ];
    }

}