<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Reflection,
    UniMapper\Exceptions\PropertyException;

/**
 * Entity property mapping object
 */
class Mapping
{

    /** @var array $definitions Collection of mapping */
    protected $definitions = array();

    public function __construct($propertyName, $definition, $rawDefinition, Reflection\Entity $entityReflection)
    {
        $definition = trim($definition);
        if ($definition === "") {
            throw new PropertyException(
                "Mapping definition can not be empty!",
                $entityReflection,
                $rawDefinition
            );
        }

        foreach (explode("|", $definition) as $definitionItem) {

            list($mapperName, $name) = explode(":", $definitionItem);

            if ($name == null) {
                $name = $propertyName;
            }

            $entityMappers = $entityReflection->getMappers();
            if (!isset($entityMappers[$mapperName])) {
                throw new PropertyException(
                    "Mapper with name " . $mapperName . " not defined!",
                    $entityReflection,
                    $rawDefinition
                );
            }

            // Only one mapping definition allowed for every mapper
            if (isset($this->definitions[$mapperName])) {
                throw new PropertyException(
                    "Only one mapping is allowed for mapper " . $mapperName . "!",
                    $entityReflection,
                    $rawDefinition
                );
            }

            $this->definitions[$mapperName] = $name;
        }
    }

    /**
     * Get mapped entity property name
     *
     * @param string $mapperName
     *
     * @return string | false
     */
    public function getName($mapperName)
    {
        if (isset($this->definitions[$mapperName])) {
            return $this->definitions[$mapperName];
        }
        return false;
    }

    public function isHybrid()
    {
        return count($this->definitions) > 1;
    }

}