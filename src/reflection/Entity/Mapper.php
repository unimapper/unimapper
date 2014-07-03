<?php

namespace UniMapper\Reflection;

use UniMapper\Exception\PropertyException;

/**
 * Entity mapper definition
 */
class Mapper
{

    /** @var string */
    protected $name = null;

    /** @var \UniMapper\Reflection\Entity $entityReflection */
    protected $entityReflection;

    /** @var string $definition Raw property docblok definition */
    protected $rawDefinition;

    public function __construct($definition, Entity $entityReflection)
    {
        $this->rawDefinition = $definition;
        $this->entityReflection = $entityReflection;

        if ($definition === "") {
            throw new PropertyException(
                "Mapper name is not set!",
                $this->entityReflection,
                $this->rawDefinition
            );
        }

        if (preg_match("#(.*?)\((.*?)\)#s", $definition, $matches)) {
            // eg. MyMapperName(resource_name)
            $this->name = $matches[1];
            $this->resource = $matches[2];
        } else {
            throw new PropertyException(
                "Invalid mapper definition!",
                $this->entityReflection,
                $this->rawDefinition
            );
        }
    }

    /**
     * Get mapper name
     *
     * @return string
     *
     * @throws \UniMapper\Exception\PropertyException
     */
    public function getName()
    {
        if ($this->name === null) {
            throw new PropertyException(
                "Mapper name is not set!",
                $this->entityReflection,
                $this->rawDefinition
            );
        }
        return $this->name;
    }

    /**
     * Get resource name, eg. table name, ...
     *
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

}