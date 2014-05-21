<?php

namespace UniMapper\Tests\Fixtures\Mapper;

class Simple extends \UniMapper\Mapper
{
    public function count(\UniMapper\Query\Count $query)
    {
        throw new \Exception("You should  mock here!");
    }

    public function delete(\UniMapper\Query\Delete $query)
    {
        throw new \Exception("You should  mock here!");
    }

    public function findOne(\UniMapper\Query\FindOne $query)
    {
        throw new \Exception("You should  mock here!");
    }

    public function findAll(\UniMapper\Query\FindAll $query)
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

    public function custom(\UniMapper\Query\Custom $query)
    {
        throw new \Exception("You should  mock here!");
    }
}
