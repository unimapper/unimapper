<?php

namespace UniMapper;

/**
 * @method \UniMapper\Query\Associate associate($primaryValue, Association $association)
 * @method \UniMapper\Query\Find      find()
 * @method \UniMapper\Query\FindOne   findOne($primaryValue)
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

    /** @var array $created Created queries  */
    protected $created = [];

    /** @var EntityFactory */
    protected $entityFactory;

    /** @var array */
    protected $queries = [
        "associate" => "UniMapper\Query\Associate",
        "count" => "UniMapper\Query\Count",
        "raw" => "UniMapper\Query\Raw",
        "delete" => "UniMapper\Query\Delete",
        "deleteOne" => "UniMapper\Query\DeleteOne",
        "find" => "UniMapper\Query\Find",
        "findOne" => "UniMapper\Query\FindOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update",
        "updateOne" => "UniMapper\Query\UpdateOne"
    ];

    public function __construct(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
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
        $entityReflection = $this->entityFactory->getEntityReflection($arguments[0]);

        unset($arguments[0]);
        array_unshift($arguments, $entityReflection, $this->adapters);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);

        if ($this->entityFactory->getCache()) {
            $query->setCache($this->entityFactory->getCache());
        }

        $this->created[] = $query;

        return $query;
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

    public function registerAdapter($name, Adapter\IAdapter $adapter)
    {
        if (isset($this->adapters[$name])) {
            throw new Exception\InvalidArgumentException(
                "Adapter with name " . $name . " already registered!"
            );
        }

        $this->adapters[$name] = $adapter;
    }

    public function getEntityFactory()
    {
        return $this->entityFactory;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getAdapters()
    {
        return $this->adapters;
    }

}