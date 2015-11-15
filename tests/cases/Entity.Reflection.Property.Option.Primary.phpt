<?php

use Tester\Assert;
use UniMapper\Entity;
use UniMapper\NamingConvention as UNC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionPropertyOptionPrimaryTest extends TestCase
{

    public function testIsEmpty()
    {
        Assert::false(Entity\Reflection\Property\Option\Primary::isEmpty(0));
        Assert::false(Entity\Reflection\Property\Option\Primary::isEmpty(0.0));
        Assert::false(Entity\Reflection\Property\Option\Primary::isEmpty("foo"));
        Assert::false(Entity\Reflection\Property\Option\Primary::isEmpty(" "));
        Assert::true(Entity\Reflection\Property\Option\Primary::isEmpty(null));
        Assert::true(Entity\Reflection\Property\Option\Primary::isEmpty(""));
    }

    public function testCreateInvalidType()
    {
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryDate")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_DATE . "' given!"
        );
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryDateTime")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_DATETIME . "' given!"
        );
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryEntity")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_ENTITY . "' given!"
        );
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryCollection")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_COLLECTION . "' given!"
        );
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryBoolean")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_BOOLEAN . "' given!"
        );
        Assert::exception(
            function() {
                Entity\Reflection::load("PrimaryArray")->getProperty("id");
            },
            "UniMapper\Exception\ReflectionException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_ARRAY . "' given!"
        );
    }

}

/** @property Date $id m:primary */
class PrimaryDate extends UniMapper\Entity {}

/** @property DateTime $id m:primary */
class PrimaryDateTime extends UniMapper\Entity {}

/** @property PrimaryDate $id m:primary */
class PrimaryEntity extends UniMapper\Entity {}

/** @property PrimaryDate[] $id m:primary */
class PrimaryCollection extends UniMapper\Entity {}

/** @property array $id m:primary */
class PrimaryArray extends UniMapper\Entity {}

/** @property bool $id m:primary */
class PrimaryBoolean extends UniMapper\Entity {}

$testCase = new EntityReflectionPropertyOptionPrimaryTest;
$testCase->run();