<?php

namespace UniMapper;

/**
 * @method \UniMapper\Query\Select    select()
 * @method \UniMapper\Query\SelectOne selectOne($primaryValue)
 * @method \UniMapper\Query\Insert    insert(array $data)
 * @method \UniMapper\Query\Update    update(array $data)
 * @method \UniMapper\Query\UpdateOne updateOne($primaryValue, array $data)
 * @method \UniMapper\Query\Delete    delete()
 * @method \UniMapper\Query\DeleteOne deleteOne($primaryValue)
 * @method \UniMapper\Query\Count     count()
 */
class QueryBuilder
{

    /** @var array */
    protected $adapters = [];

    /** @var Cache\ICache */
    protected $cache;

    /** @var Mapper */
    protected $mapper;

    /** @var array */
    protected $queries = [
        "count" => "UniMapper\Query\Count",
        "raw" => "UniMapper\Query\Raw",
        "delete" => "UniMapper\Query\Delete",
        "deleteOne" => "UniMapper\Query\DeleteOne",
        "select" => "UniMapper\Query\Select",
        "selectOne" => "UniMapper\Query\SelectOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update",
        "updateOne" => "UniMapper\Query\UpdateOne"
    ];

    protected $beforeQuery = [];

    protected $afterQuery = [];

    public function __construct(Mapper $mapper, Cache\ICache $cache = null)
    {
        $this->mapper = $mapper;
        $this->cache = $cache;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->queries[$name])) {
            throw new Exception\InvalidArgumentException(
                "Query with name " . $name . " does not exist!"
            );
        }

        if (!isset($arguments[0])) {
            throw new Exception\InvalidArgumentException(
                "You must pass queried entity name!"
            );
        }
        $entityReflection = Reflection\Loader::load($arguments[0]);

        unset($arguments[0]);
        array_unshift($arguments, $entityReflection, $this->adapters, $this->mapper);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);

        if ($this->cache) {
            $query->setCache($this->cache);
        }

        foreach ($this->beforeQuery as $callback) {
            $query->beforeExecute($callback);
        }

        foreach ($this->afterQuery as $callback) {
            $query->afterExecute($callback);
        }

        return $query;
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

    /**
     * Register custom query
     *
     * @param string $class
     */
    public function registerQuery($class)
    {
        $this->queries[$class::getName()] = $class;
    }

    public function getAdapters()
    {
        return $this->adapters;
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