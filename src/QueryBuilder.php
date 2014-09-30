<?php

namespace UniMapper;

use UniMapper\Reflection;

/**
 * @method \UniMapper\Query\Find      find()
 * @method \UniMapper\Query\FindOne   findOne($primaryValue)
 * @method \UniMapper\Query\Insert    insert(array $data)
 * @method \UniMapper\Query\Update    update(array $data)
 * @method \UniMapper\Query\UpdateOne updateOne($primaryValue, array $data)
 * @method \UniMapper\Query\Delete    delete()
 * @method \UniMapper\Query\Count     count()
 */
class QueryBuilder
{

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    /** @var array */
    protected $adapters = [];

    /** @var \UniMapper\Logger */
    protected $logger;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    /** @var array */
    protected $queries = [
        "count" => "UniMapper\Query\Count",
        "raw" => "UniMapper\Query\Raw",
        "delete" => "UniMapper\Query\Delete",
        "find" => "UniMapper\Query\Find",
        "findOne" => "UniMapper\Query\FindOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update",
        "updateOne" => "UniMapper\Query\UpdateOne"
    ];

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        Cache\ICache $cache = null,
        Logger $logger = null
    ) {
        $this->entityReflection = $entityReflection;
        $this->adapters = $adapters;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->queries[$name])) {
            throw new Exception\InvalidArgumentException(
                "Query with name " . $name . " does not exist!"
            );
        }

        array_unshift($arguments, $this->entityReflection, $this->adapters);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);

        if ($this->cache) {
            $query->setCache($this->cache);
        }

        if ($this->logger) {
            $this->logger->logQuery($query);
        }

        return $query;
    }

    public function registerQuery($class)
    {
        $this->queries[$class::getName()] = $class;
    }

}