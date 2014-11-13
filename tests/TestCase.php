<?php

namespace UniMapper\Tests;

use UniMapper\Reflection;
use UniMapper\NamingConvention as UNC;

class TestCase extends \Tester\TestCase
{

    protected function createEntity($name, $values = [])
    {
        $class = UNC::nameToClass($name, UNC::$entityMask);
        return new $class(new Reflection\Entity($class), $values);
    }

    protected function createRepository($name, array $adapters = [])
    {
        $queryBuilder = new \UniMapper\QueryBuilder(new \UniMapper\EntityFactory);
        foreach ($adapters as $adapter) {
            $queryBuilder->registerAdapter($adapter);
        }

        $class = UNC::nameToClass($name, UNC::$repositoryMask);
        return new $class($queryBuilder);
    }

}