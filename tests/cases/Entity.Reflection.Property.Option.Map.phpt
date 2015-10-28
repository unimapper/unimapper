<?php

use UniMapper\Entity;
use UniMapper\NamingConvention as UNC;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

UNC::setMask("*", UNC::ENTITY_MASK);

/**
 * @property array $map        m:map-by(unmapped_id) m:map-filter(stringToArray|arrayToString)
 * @property array $fullFilter m:map-filter(Map::stringToArray|Map::arrayToString)
 */
class Map extends Entity
{
    public static function stringToArray($value)
    {
        return explode(',', $value);
    }

    public static function arrayToString($value)
    {
        return implode(',', $value);
    }
}

/** @property array $map m:map-filter() */
class EmptyFilter extends Entity {}

/** @property array $map m:map-filter(UndefinedInputFilterMethod|Map::arrayToString) */
class UndefinedInputFilterMethod extends Entity {}

/** @property array $map m:map-filter(Map::stringToArray|UndefinedOutputFilterMethod) */
class UndefinedOutputFilterMethod extends Entity {}

/**
 * @testCase
 */
class EntityReflectionPropertyOptionMapTest extends \Tester\TestCase
{

    public function testCreateWithMapBy()
    {
        Assert::same(
            "unmapped_id",
            Entity\Reflection::load("Map")
                ->getProperty("map")
                ->getOption(Entity\Reflection\Property\Option\Map::KEY)
                ->getUnMapped()
        );
    }

    public function testCreateWithFilter()
    {
        $map = Entity\Reflection::load("Map")
            ->getProperty("map")
            ->getOption(Entity\Reflection\Property\Option\Map::KEY);

        Assert::true(is_callable($map->getFilterIn()));
        Assert::true(is_callable($map->getFilterOut()));
    }

    public function testCreateWithFullFilter()
    {
        $map = Entity\Reflection::load("Map")
            ->getProperty("fullFilter")
            ->getOption(Entity\Reflection\Property\Option\Map::KEY);

        Assert::true(is_callable($map->getFilterIn()));
        Assert::true(is_callable($map->getFilterOut()));
    }

    /**
     * @throws UniMapper\Exception\EntityException You must define input/output filter!
     */
    public function testCreateWithEmptyFilter()
    {
        Entity\Reflection::load("EmptyFilter");
    }

    /**
     * @throws UniMapper\Exception\EntityException Invalid input filter definition!
     */
    public function testCreateUndefinedInputFilterMethod()
    {
        Entity\Reflection::load("UndefinedInputFilterMethod");
    }

    /**
     * @throws UniMapper\Exception\EntityException Invalid output filter definition!
     */
    public function testCreateUndefinedOutputFilterMethod()
    {
        Entity\Reflection::load("UndefinedOutputFilterMethod");
    }

}

$testCase = new EntityReflectionPropertyOptionMapTest;
$testCase->run();