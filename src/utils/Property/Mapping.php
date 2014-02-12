<?php

namespace UniMapper\Utils\Property;

use UniMapper\Exceptions\PropertyException;

/**
 * Entity property mapping object
 */
class Mapping
{

    /** @var array $definitions Collection of mapping */
    protected $definitions = array();

    /**
     * Constructor
     *
     * @param string           $propertyName  Property name
     * @param string           $definition    Mapping definition
     * @param string           $rawDefinition Raw property definition
     * @param \ReflectionClass $reflection    Entity reflection
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function __construct($propertyName, $definition, $rawDefinition, \ReflectionClass $reflection)
    {
        $definition = trim($definition);

        if (empty($definition)) {
            throw new PropertyException(
                "Empty mapping definition found!",
                $reflection,
                $rawDefinition
            );
        }

        $definitionItems = explode("|", $definition);

        foreach ($definitionItems as $definitionItem) {

            list($class, $name) = explode(":", $definitionItem);

            $class = "UniMapper\Mapper\\" . $class . "Mapper";
            if (!class_exists($class)) {
                throw new PropertyException(
                    "Mapper " . $class . " not found!",
                    $reflection,
                    $rawDefinition
                );
            }

            // Only one mapping definition allowed for every mapper
            if (isset($this->definitions[$class])) {
                throw new PropertyException(
                    "Only one mapping is allowed for every mapper!",
                    $reflection,
                    $rawDefinition
                );
            }

            if ($name == null) {
                $name = $propertyName;
            }

            $this->definitions[$class] = $name;
        }
    }

    /**
     * Get mapped entity property name according to mapper class
     *
     * @param string $mapperClass Mapper class
     *
     * @return string | false
     */
    public function getName($mapperClass)
    {
        if (isset($this->definitions[$mapperClass])) {
            return $this->definitions[$mapperClass];
        }
        return false;
    }

    public function isHybrid()
    {
        return count($this->definitions) > 1;
    }

}