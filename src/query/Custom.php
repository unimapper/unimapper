<?php

namespace UniMapper\Query;

class Custom extends \UniMapper\Query
{

    const METHOD_RAW = "raw",
          METHOD_GET = "get",
          METHOD_PUT = "put",
          METHOD_POST = "post",
          METHOD_DELETE = "delete";

    public $query;
    public $method;
    public $data;
    public $contentType;

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

    public function put($query, $data, $contentType = "")
    {
        $this->method = self::METHOD_PUT;
        $this->query = $query;
        $this->data = $data;
        $this->contentType = $contentType;
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

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        return $mapper->custom(
            $mapper->getResource($this->entityReflection),
            $this->query,
            $this->method,
            $this->contentType,
            $this->data
        );
    }

}