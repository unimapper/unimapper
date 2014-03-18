<?php

namespace UniMapper;

use UniMapper\Reflection\EntityReflection,
    UniMapper\Exceptions\QueryBuilderException;

class QueryBuilder
{

    protected $entityReflection;
    protected $mappers;
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

    public function __construct(EntityReflection $entityReflection, array $mappers, Logger $logger = null)
    {
        $this->entityReflection = $entityReflection;
        $this->mappers = $mappers;
        $this->logger = $logger;
    }

    public function __call($name, $arguments)
    {
        if (!isset($this->queries[$name])) {
            throw new QueryBuilderException("Query with name " . $name . " not registered!");
        }

        array_unshift($arguments, $this->entityReflection, $this->mappers);

        $class = new \ReflectionClass($this->queries[$name]);
        $query = $class->newInstanceArgs($arguments);
        $this->logger->logQuery($query);
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