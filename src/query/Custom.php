<?php

namespace UniMapper\Query;

use UniMapper\Reflection\EntityReflection,
    UniMapper\Exceptions\QueryException;

/**
 * ORM query object
 */
class Custom extends \UniMapper\Query
{

    const METHOD_GET = "get",
          METHOD_PUT = "put",
          METHOD_POST = "post",
          METHOD_DELETE = "delete";

    public $query;
    public $method;
    public $mapper;
    public $data;

    public function __construct(EntityReflection $entityReflection, array $mappers, $mapperName)
    {
        parent::__construct($entityReflection, $mappers);
        if (!isset($this->mappers[$mapperName])) {
            throw new QueryException("Mapper " . $mapperName . " not set!");
        }
        $this->mapper = $this->mappers[$mapperName];
    }

    public function get($query)
    {
        $this->method = self::METHOD_GET;
        $this->query = $query;
        return $this;
    }

    public function put($query, $data)
    {
        $this->method = self::METHOD_PUT;
        $this->query = $query;
        $this->data = $data;
        return $this;
    }

    public function post($query, $data)
    {
        $this->method = self::METHOD_POST;
        $this->query = $query;
        $this->data = $data;
        return $this;
    }

    public function delete($query)
    {
        $this->method = self::METHOD_DELETE;
        $this->query = $query;
        return $this;
    }

    public function onExecute()
    {
        return $this->mapper->custom($this);
    }

}