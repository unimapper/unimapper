<?php

namespace UniMapper\Entity\Reflection;

class Enumeration
{

    /** @var array $values */
    private $values = [];

    /** @var array $index */
    private $index = [];

    public function __construct($class, $prefix)
    {
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