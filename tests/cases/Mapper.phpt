<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AdapterConvention.php';

/**
 * @testCase
 */
class MapperTest extends TestCase
{

    /** @var \UniMapper\Mapper */
    private $mapper;

    public function setUp()
    {
        \UniMapper\Convention::registerAdapterConvention("Foo", new AdapterConvention);
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
                "filter_array" => "one,two,three",
                "filter_int" => "1",
                "disabled" => 1
            ]
        );

        Assert::type("Entity", $entity);
        Assert::same(1, $entity->integer);
        Assert::null($entity->disabled);
        Assert::same(1, $entity->readonly);
        Assert::same(["one", "two", "three"], $entity->filterArray);
        Assert::same(1, $entity->filterInt);
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
                'filter_array' => '1,2,3',
                'filter_int' => '1',
                'entity' => array('integer' => 1)
            ),
            $this->mapper->unmapEntity(
                new Entity(
                    [
                        "string" => "foo",
                        "filterArray" => [1, 2, 3],
                        "filterInt" => 1,
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
            [
                "filter_array" => [\UniMapper\Entity\Filter::EQUAL => "1,2"],
                "filter_int" => [\UniMapper\Entity\Filter::EQUAL => ["1"]],
                "integer" => [\UniMapper\Entity\Filter::EQUAL => [1]]
            ],
            $this->mapper->unmapFilter(
                Reflection::load("Entity"),
                [
                    "filterArray" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]],
                    "filterInt" => [\UniMapper\Entity\Filter::EQUAL => [1]],
                    "integer" => [\UniMapper\Entity\Filter::EQUAL => [1]]
                ]
            )
        );
    }

    public function testUnmapFilterGroup()
    {
        Assert::same(
            [["filter_array" => [\UniMapper\Entity\Filter::EQUAL => "1,2"]]],
            $this->mapper->unmapFilter(
                Reflection::load("Entity"),
                [["filterArray" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]]
            )
        );
    }

}

/**
 * @adapter Foo
 *
 * @property int      $integer
 * @property string   $string
 * @property string   $disabled    m:map(false)
 * @property array    $filterArray m:map-filter(stringToArray|arrayToString) m:map-by(filter_array)
 * @property int      $filterInt   m:map-filter(toInt|toString)
 * @property array    $array
 * @property Entity   $entity
 * @property Entity[] $collection
 * @property int      $fooBar
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

    public static function toInt($value)
    {
        return (int) $value;
    }

    public static function toString($value)
    {
        return (string) $value;
    }
}

$testCase = new MapperTest;
$testCase->run();