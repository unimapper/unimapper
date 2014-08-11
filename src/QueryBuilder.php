<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Exception\QueryBuilderException;

/**
 * @method \UniMapper\Query\FindAll   findAll()
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

    /** @var array */
    protected $queries = array(
        "count" => "UniMapper\Query\Count",
        "raw" => "UniMapper\Query\Raw",
        "delete" => "UniMapper\Query\Delete",
        "findAll" => "UniMapper\Query\FindAll",
        "findOne" => "UniMapper\Query\FindOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update",
        "updateOne" => "UniMapper\Query\UpdateOne"
    );

    public function __construct(Reflection\Entity $entityReflection,
        array $adapters, Logger $logger = null
    ) {
        $this->entityReflection = $entityReflection;
        $this->adapters = $adapters;
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->queries[$name])) {
            throw new QueryBuilderException(
                "Query with name " . $name . " does not exist!"
            );
        }

        array_unshift($arguments, $this->entityReflection, $this->adapters);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);

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