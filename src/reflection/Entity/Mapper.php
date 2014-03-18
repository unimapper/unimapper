<?php

namespace UniMapper\Reflection;

use UniMapper\Exceptions\PropertyException;

/**
 * Entity mapper definition
 */
class Mapper
{

    /** @var string */
    protected $name = null;

    /** @var \ReflectionClass $reflection Entity reflection */
    protected $reflection;

    /** @var string $definition Raw property docblok definition */
    protected $rawDefinition;

    /**
     * Constructor
     *
     * @param string           $definition Property definition
     * @param \ReflectionClass $reflection Entity reflection class
     */
    public function __construct($definition, \ReflectionClass $reflection)
    {
        $this->rawDefinition = $definition;
        $this->reflection = $reflection;

        if ($definition === "") {
            throw new PropertyException(
                "Mapper name is not set!",
                $this->reflection,
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
                $this->reflection,
                $this->rawDefinition
            );
        }
    }

    /**
     * Get mapper name
     *
     * @return string
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function getName()
    {
        if ($this->name === null) {
            throw new PropertyException(
                "Mapper name is not set!",
                $this->reflection,
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