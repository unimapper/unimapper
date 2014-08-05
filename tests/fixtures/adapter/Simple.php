<?php

namespace UniMapper\Tests\Fixtures\Adapter;

class Simple extends \UniMapper\Adapter
{

    public function count($resource, $conditions)
    {
        throw new \Exception("You should  mock here!");
    }

    public function delete($resource, $conditions)
    {
        throw new \Exception("You should  mock here!");
    }

    public function findOne($resource, $primaryName, $primaryValue, array $associations = [])
    {
        throw new \Exception("You should  mock here!");
    }

    public function findAll($resource, $selection = null, $conditions = null, $orderBy = null, $limit = 0, $offset = 0, array $associations = [])
    {
        throw new \Exception("You should  mock here!");
    }

    public function insert($resource, array $values)
    {
        throw new \Exception("You should  mock here!");
    }

    public function update($resource, array $values, $conditions = null)
    {
        throw new \Exception("You should  mock here!");
    }

    public function updateOne($resource, $primaryName, $primaryValue, array $values)
    {
        throw new \Exception("You should  mock here!");
    }

    public function raw()
    {
        throw new \Exception("You should  mock here!");
    }

}
