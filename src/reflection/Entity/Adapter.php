<?php

namespace UniMapper\Reflection;

use UniMapper\Exception;

/**
 * Entity adapter definition
 */
class Adapter
{

    /** @var string */
    private $name;

    public function __construct($definition)
    {
        if ($definition === "") {
            throw new Exception\DefinitionException(
                "Adapter name is not set!"
            );
        }

        if (preg_match("#(.*?)\((.*?)\)#s", $definition, $matches)) {
            // eg. MyAdapterName(resource_name)

            $this->name = trim($matches[1]);
            if (empty($this->name)) {
                throw new Exception\DefinitionException(
                    "Adapter name is not defined"
                );
            }

            $this->resource = $matches[2];
        } else {
            throw new Exception\DefinitionException(
                "Invalid adapter definition!"
            );
        }
    }

    /**
     * Get adapter name
     *
     * @return string
     */
    public function getName()
    {
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