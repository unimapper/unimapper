<?php

namespace UniMapper;

/**
 * Adapter is generally used to communicate between repository and data source.
 */
abstract class Adapter implements Adapter\IAdapter
{

    /** @var string */
    protected $name;

    /** @var \UniMapper\Mapping */
    protected $mapping;

    public function __construct($name, Mapping $mapping)
    {
        $this->name = $name;
        $this->mapping = $mapping;
    }

    public function setCache(Cache $cache)
    {
        $this->mapping->setCache($cache);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMapping()
    {
        return $this->mapping;
    }

}