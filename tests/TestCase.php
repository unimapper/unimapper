<?php

namespace UniMapper\Tests;

use UniMapper\Reflection;
use UniMapper\NamingConvention as NC;

class TestCase extends \Tester\TestCase
{

    protected function createEntity($name, $values = [])
    {
        $class = NC::nameToClass($name, NC::$entityMask);
        return new $class(new Reflection\Entity($class), $values);
    }

}