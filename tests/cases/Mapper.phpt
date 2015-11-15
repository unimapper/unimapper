<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class MapperTest extends TestCase
{

    /** @var \UniMapper\Mapper */
    private $mapper;

    public function setUp()
    {
        $this->mapper = new UniMapper\Mapper;
    }

    public function testMapEntity()
    {
        $entity = $this->mapper->mapEntity(
            "Entity",
            [
                "integer" => "1",
                "undefined" => 1,
                "readonly" => 1,
                "custom_filter" => "one,two,three",
                "disabled" => 1
            ]
        );

        Assert::type("Entity", $entity);
        Assert::same(1, $entity->integer);
        Assert::null($entity->disabled);
        Assert::same(1, $entity->readonly);
        Assert::same(["one", "two", "three"], $entity->filter);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Traversable value can not be mapped to scalar!
     */
    public function testMapValueArrayToString()
    {
        $this->mapper->mapValue(
            Reflection::load("Entity")->getProperty("string"),
            []
        );
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Traversable value can not be mapped to scalar!
     */
    public function testMapValueObjectToString()
    {
        $this->mapper->mapValue(
            Reflection::load("Entity")->getProperty("string"),
            new stdClass
        );
    }

    /**
     * @throws \UniMapper\Exception\InvalidArgumentException Mapping disabled on property disabled!
     */
    public function testMapValueMappingDisabled()
    {
        $this->mapper->mapValue(Reflection::load("Entity")->getProperty("disabled"), 1);
    }

    public function testUnmapEntity()
    {
        Assert::same(
            array(
                'string' => 'foo',
                'custom_filter' => '1,2,3',
                'entity' => array('integer' => 1)
            ),
            $this->mapper->unmapEntity(
                new Entity(
                    [
                        "string" => "foo",
                        "filter" => [1, 2, 3],
                        "entity" => new Entity(["integer" => 1]),
                        "readonly" => "foo",
                        "disabled" => 1
                    ]
                )
            )
        );
    }

    public function testMapCollection()
    {
        $collection = $this->mapper->mapCollection(
            "Entity",
            [["integer" => "1"]]
        );
        Assert::type("UniMapper\Entity\Collection", $collection);
        Assert::count(1, $collection);
        Assert::type("Entity", $collection[0]);
        Assert::same(1, $collection[0]->integer);
    }

    public function testUnmapCollection()
    {
        $collection = Entity::createCollection([["integer" => 1]]);

        Assert::same(
            [["integer" => 1]],
            $this->mapper->unmapCollection($collection)
        );
    }

    public function testUnmapFilter()
    {
        Assert::same(
            ["custom_filter" => [\UniMapper\Entity\Filter::EQUAL => "1,2"]],
            $this->mapper->unmapFilter(
                Reflection::load("Entity"),
                ["filter" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]
            )
        );
    }

    public function testUnmapFilterGroup()
    {
        Assert::same(
            [["custom_filter" => [\UniMapper\Entity\Filter::EQUAL => "1,2"]]],
            $this->mapper->unmapFilter(
                Reflection::load("Entity"),
                [["filter" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]]
            )
        );
    }

}

/**
 * @adapter Foo
 *
 * @property int      $integer
 * @property string   $string
 * @property string   $disabled   m:map(false)
 * @property array    $filter     m:map-filter(stringToArray|arrayToString) m:map-by(custom_filter)
 * @property Entity   $entity
 * @property Entity[] $collection
 *
 * @property-read int $readonly
 */
class Entity extends \UniMapper\Entity
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

$testCase = new MapperTest;
$testCase->run();