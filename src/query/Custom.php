<?php

namespace UniMapper\Query;

use UniMapper\Reflection,
    UniMapper\Exceptions\QueryException;

class Custom extends \UniMapper\Query
{

    const METHOD_RAW = "raw",
          METHOD_GET = "get",
          METHOD_PUT = "put",
          METHOD_POST = "post",
          METHOD_DELETE = "delete";

    public $query;
    public $method;
    public $mapper;
    public $data;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, $mapperName)
    {
        parent::__construct($entityReflection, $mappers);
        if (!isset($this->mappers[$mapperName])) {
            throw new QueryException("Mapper " . $mapperName . " not set!");
        }
        $this->mapper = $this->mappers[$mapperName];
    }

    public function raw($args)
    {
        $args = func_get_args();
        $this->method = self::METHOD_RAW;
        $this->query = $args;
        return $this;
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

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        return $this->mapper->custom($this);
    }

    public function executeHybrid()
    {
        return $this->executeSimple($this->mapper); // @todo
    }

}