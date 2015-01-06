<?php

namespace UniMapper\Tests;

use UniMapper\NamingConvention as UNC;

class TestCase extends \Tester\TestCase
{

    protected function createEntity($name, $values = [])
    {
        $class = UNC::nameToClass($name, UNC::ENTITY_MASK);
        return new $class($values);
    }

    protected function createRepository($name, array $adapters = [])
    {
        $queryBuilder = new \UniMapper\QueryBuilder(new \UniMapper\Mapper);
        foreach ($adapters as $adapterName => $adapter) {
            $queryBuilder->registerAdapter($adapterName, $adapter);
        }

        $class = UNC::nameToClass($name, UNC::REPOSITORY_MASK);
        return new $class($queryBuilder);
    }

}