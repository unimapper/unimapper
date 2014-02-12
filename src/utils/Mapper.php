<?php

namespace UniMapper\Utils;

use UniMapper\Exceptions\PropertyException;

/**
 * Entity mapper definition
 */
class Mapper
{

    /** @var string */
    protected $name = null;

    /** @var string */
    protected $class = null;

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

        $this->readDefinition($definition);
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
     * Get mapper class
     *
     * @return string
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public function getClass()
    {
        if ($this->class === null) {
            throw new PropertyException(
                "Mapper class is not set!",
                $this->reflection,
                $this->rawDefinition
            );
        }
        return $this->class;
    }

    /**
     * Read and set mapper definition from docblock
     *
     * @param string $definition Docblok definition
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    protected function readDefinition($definition)
    {
        if (preg_match("#(.*?)\((.*?)\)#s", $definition, $matches)) {
            // eg. MyMapperName(database_tableName)
            $name = $matches[1];
            $this->resource = $matches[2];
        } elseif (class_exists("UniMapper\Mapper\\" . $definition . "Mapper")) {
            $name = $definition;
        } else {
            throw new PropertyException(
                "Invalid mapper definition!",
                $this->reflection,
                $this->rawDefinition
            );
        }
        $this->name = $name;
        $this->class = "UniMapper\Mapper\\" . $name . "Mapper";
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