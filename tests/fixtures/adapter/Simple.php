<?php

namespace UniMapper\Tests\Fixtures\Adapter;

class Simple extends \UniMapper\Adapter
{

    public function count($resource, array $conditions)
    {
        throw new \Exception("You should  mock here!");
    }

    public function delete($resource, array $conditions)
    {
        throw new \Exception("You should  mock here!");
    }

    public function findOne($resource, $primaryName, $primaryValue, array $associations = [])
    {
        throw new \Exception("You should  mock here!");
    }

    public function findAll($resource, array $selection = [], array $conditions = [], array $orderBy = [], $limit = 0, $offset = 0, array $associations = [])
    {
        throw new \Exception("You should  mock here!");
    }

    public function insert($resource, array $values)
    {
        throw new \Exception("You should  mock here!");
    }

    public function update($resource, array $values, array $conditions)
    {
        throw new \Exception("You should  mock here!");
    }

    public function updateOne($resource, $primaryName, $primaryValue, array $values)
    {
        throw new \Exception("You should  mock here!");
    }

    public function custom($resource, $query, $method, $contentType, $data)
    {
        throw new \Exception("You should  mock here!");
    }

}
