<?php

namespace UniMapper;

use UniMapper\Mapper,
    UniMapper\Reflection,
    UniMapper\Exceptions\QueryBuilderException;

/**
 * @method \UniMapper\Query\FindAll findAll()
 * @method \UniMapper\Query\FindOne findOne($primaryValue)
 * @method \UniMapper\Query\Insert  insert(array $data)
 * @method \UniMapper\Query\Update  update(array $data)
 * @method \UniMapper\Query\Delete  delete()
 * @method \UniMapper\Query\Count   count()
 */
class QueryBuilder
{

    protected $entityReflection;
    protected $mapper;
    protected $logger;
    protected $queries = array(
        "count" => "UniMapper\Query\Count",
        "custom" => "UniMapper\Query\Custom",
        "delete" => "UniMapper\Query\Delete",
        "findAll" => "UniMapper\Query\FindAll",
        "findOne" => "UniMapper\Query\FindOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update"
    );

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, Logger $logger = null)
    {
        $this->entityReflection = $entityReflection;
        $this->mapper = $mapper;
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->queries[$name])) {
            throw new QueryBuilderException("Query with name " . $name . " not registered!");
        }

        array_unshift($arguments, $this->entityReflection, $this->mapper);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);

        if ($this->logger) {
            $this->logger->logQuery($query);
        }

        return $query;
    }

    public function registerQuery($class)
    {
        $name = $class::getName();
        if (isset($this->queries[$name]) || in_array($class, $this->queries)) {
            throw new QueryBuilderException("Query with name " . $name . " and class " . $class . " already registered!");
        }
        $this->queries[$name] = $class;
    }

}
