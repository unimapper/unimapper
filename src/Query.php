<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Exception\QueryException;

abstract class Query
{

    protected $executed = false;

    /** @var integer */
    private $elapsed;

    /** @var mixed */
    private $result;

    /** @var array */
    protected $adapters = [];

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    /** @var array $adapterQueries List of queries executed on adapter */
    protected $adapterQueries = [];

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
        $this->result = $this->onExecute($this->adapters[$adapterName]);
        $this->elapsed = microtime(true) - $start;
        $this->executed = true;

        return $this->result;
    }

}