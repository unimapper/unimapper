<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Exception\QueryException;

abstract class Query
{

    /** @var array */
    protected $adapters = [];

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    /** @var array */
    private $beforeExecute = [];

    /** @var array */
    private $afterExecute = [];

    public function __construct(Reflection\Entity $reflection, array $adapters)
    {
        if (!$reflection->hasAdapter()) {
            throw new QueryException(
                "Entity '" . $reflection->getClassName()
                . "' has no adapter defined!"
            );
        }

        if (!isset($adapters[$reflection->getAdapterReflection()->getName()])) {
            throw new QueryException(
                "Adapter '" . $reflection->getAdapterReflection()->getName()
                . "' not given!"
            );
        }

        $this->adapters = $adapters;
        $this->entityReflection = $reflection;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function setCache(\UniMapper\Cache\ICache $cache)
    {
        $this->cache = $cache;
    }

    public function beforeExecute(callable $callback)
    {
        $this->beforeExecute[] = $callback;
    }

    public function afterExecute(callable $callback)
    {
        $this->afterExecute[] = $callback;
    }

    public static function getName()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return lcfirst($reflection->getShortName());
    }

    final public function execute()
    {
        $start = microtime(true);

        $adapterName = $this->entityReflection->getAdapterReflection()->getName();
        if (!isset($this->adapters[$adapterName])) {
            throw new QueryException(
                "Adapter with name '" . $adapterName . "' not given!"
            );
        }

        foreach ($this->beforeExecute as $callback) {

            // function(\UniMapper\Query $query)
            $callback($this);
        }

        $result = $this->onExecute($this->adapters[$adapterName]);
        foreach ($this->afterExecute as $callback) {

            // function(\UniMapper\Query $query, mixed $result, int $elapsed)
            $callback($this, $result, microtime(true) - $start);
        }

        return $result;
    }

}