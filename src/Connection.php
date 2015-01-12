<?php

namespace UniMapper;

class Connection
{

    /** @var array */
    protected $adapters = [];

    /** @var Cache\ICache */
    protected $cache;

    /** @var Mapper */
    protected $mapper;

    protected $beforeQuery = [];

    protected $afterQuery = [];

    public function __construct(Mapper $mapper, Cache\ICache $cache = null)
    {
        $this->mapper = $mapper;
        $this->cache = $cache;
    }

    public function registerAdapter($name, Adapter $adapter)
    {
        if (isset($this->adapters[$name])) {
            throw new Exception\InvalidArgumentException(
                "Adapter with name " . $name . " already registered!"
            );
        }

        $this->adapters[$name] = $adapter;
        if ($adapter->getMapping()) {
            $this->mapper->registerAdapterMapping($name, $adapter->getMapping());
        }
    }

    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * Get adapter
     *
     * @param string $name
     *
     * @return Adapter
     *
     * @throws Exception\ConnectionException
     */
    public function getAdapter($name)
    {
        if (!isset($this->adapters[$name])) {
            throw new Exception\ConnectionException(
                "Adapter " . $name . " not registered on connection!"
            );
        }

        return $this->adapters[$name];
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getMapper()
    {
        return $this->mapper;
    }

    public function afterQuery(callable $callback)
    {
        $this->afterQuery[] = $callback;
    }

    public function beforeQuery(callable $callback)
    {
        $this->beforeQuery[] = $callback;
    }

}