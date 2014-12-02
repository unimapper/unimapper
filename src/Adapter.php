<?php

namespace UniMapper;

abstract class Adapter implements Adapter\IAdapter
{

    /** @var array */
    private $afterExecute = [];

    /** @var array */
    private $beforeExecute = [];

    /** @var Adapter\Mapping */
    private $mapping;

    public function __construct(Adapter\Mapping $mapping = null)
    {
        $this->mapping = $mapping;
    }

    final public function getMapping()
    {
        return $this->mapping;
    }

    public function beforeExecute(callable $callback)
    {
        $this->beforeExecute[] = $callback;
    }

    final public function execute(Adapter\IQuery $query)
    {
        foreach ($this->beforeExecute as $callback) {
            $callback($query);
        }

        $result = $this->onExecute($query);

        foreach ($this->afterExecute as $callback) {
            $callback($query, $result);
        }

        return $result;
    }

    public function afterExecute(callable $callback)
    {
        $this->afterExecute[] = $callback;
    }

}