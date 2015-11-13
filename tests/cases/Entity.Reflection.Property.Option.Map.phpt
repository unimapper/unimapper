<?php

use UniMapper\Entity;
use UniMapper\NamingConvention as UNC;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

UNC::setMask("*", UNC::ENTITY_MASK);

/**
 * @property int   $id         m:map-by(foo)
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
 * @property int    $id
 * @property string $disabled m:map(false)
 */
class Disabled extends Entity {}

/**
 * @property string $disabled m:map(false) m:map-by(foo)
 */
class DisabledButConfigured extends Entity {}

/**
 * @property int $id m:primary m:map(false)
 */
class DisabledPrimary extends Entity {}

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
                ->getUnmapped()
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

    public function testCreateDisabled()
    {
        $property = Disabled::getReflection()->getProperty("disabled");
        Assert::true($property->hasOption(Entity\Reflection\Property\Option\Map::KEY));
        Assert::false($property->getOption(Entity\Reflection\Property\Option\Map::KEY));
    }

    /**
     * @throws UniMapper\Exception\EntityException Can not configure mapping if option disabled!
     */
    public function testCreateDisabledButConfigured()
    {
        DisabledButConfigured::getReflection();
    }

    public function testCreatePrimary()
    {
        Assert::same(
            "foo",
            Map::getReflection()
                ->getProperty("id")
                ->getOption(Entity\Reflection\Property\Option\Map::KEY)
                ->getUnMapped()
        );
    }

    /**
     * @throws UniMapper\Exception\EntityException Mapping can not be disabled on primary property!
     */
    public function testCreateDisabledWithPrimary()
    {
        DisabledPrimary::getReflection();
    }

}

$testCase = new EntityReflectionPropertyOptionMapTest;
$testCase->run();