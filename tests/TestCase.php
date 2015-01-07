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

}