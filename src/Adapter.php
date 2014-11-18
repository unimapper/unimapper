<?php

namespace UniMapper;

/**
 * Adapter is generally used to communicate between repository and data source.
 */
abstract class Adapter implements Adapter\IAdapter
{

    /** @var string */
    protected $name;

    /** @var array */
    protected $options;

    public function __construct($name, array $options = [])
    {
        $this->name = $name;
        $this->options = $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function createMapper()
    {
        return new Adapter\Mapper;
    }

}