<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Tests\Fixtures\Entity\Simple */
    private $entity;

    public function setUp()
    {
        $this->entity = $this->createEntity(
            "Simple",
            [
                "text" => "test",
                "id" => 1,
                "empty" => ""
            ]
        );
    }

    public function testGetProperty()
    {
        Assert::same("test", $this->entity->text);
        Assert::same(1, $this->entity->id);
        Assert::same("", $this->entity->empty);
        Assert::type("UniMapper\EntityCollection", $this->entity->manyToMany);
        Assert::count(0, $this->entity->manyToMany);
        Assert::null($this->entity->year); // Computed
    }

    /**
     * @throws UniMapper\Exception\PropertyAccessException Undefined property 'undefined'!
     */
    public function testGetUndefinedProperty()
    {

        $this->entity->undefined;
    }

    public function testGetPublicProperty()
    {
        Assert::same("defaultValue", $this->entity->publicProperty);

        $this->entity->publicProperty = "newValue";
        Assert::same("newValue", $this->entity->publicProperty);
    }

    /**
     * @throws Exception Property 'readonly' is read-only!
     */
    public function testSetReadnonlyProperty()
    {
        $this->entity->readonly = "trytowrite";
    }

    public function testIsset()
    {
        Assert::true(isset($this->entity->id));
        Assert::true(isset($this->entity->publicProperty));
        Assert::false(isset($this->entity->missing));
    }

    public function testEmpty()
    {
        Assert::true(empty($this->entity->empty));
        Assert::true(empty($this->entity->missing));
        Assert::false(empty($this->entity->id));
    }

    public function testUnset()
    {
        unset($this->entity->id);
        Assert::null($this->entity->id);
    }

    public function testSetProperty()
    {
        $this->entity->id = 1;
        Assert::equal(1, $this->entity->id);
        $this->entity->collection[] = $this->createEntity("Nested", ["text" => "foo"]);
        Assert::same("foo", $this->entity->collection[0]->text);
    }

    public function testToArray()
    {
        $this->entity->collection[] = $this->createEntity("Nested", ["text" => "foo"]);
        $this->entity->manyToMany[] = $this->createEntity("Remote", ["id" => 1]);
        $this->entity->entity = $this->createEntity("Nested");

        Assert::type("array", $this->entity->toArray());
        Assert::count(18, $this->entity->toArray());
        Assert::same("test", $this->entity->toArray()["text"]);
        Assert::same("", $this->entity->toArray()["empty"]);
        Assert::same($this->entity->collection, $this->entity->toArray()["collection"]);
        Assert::same($this->entity->entity, $this->entity->toArray()["entity"]);
        Assert::same("defaultValue", $this->entity->toArray()["publicProperty"]);
        Assert::same($this->entity->manyToMany, $this->entity->toArray()["manyToMany"]);
    }

    public function testToArrayRecursive()
    {
        $this->entity->collection[] = $this->createEntity("Nested", ["text" => "foo"]);
        $this->entity->manyToMany[] = $this->createEntity("Remote", ["id" => 1]);
        $this->entity->entity = $this->createEntity("Nested");

        Assert::same(
            array(
                'id' => 1,
                'text' => 'test',
                'empty' => '',
                'url' => NULL,
                'email' => NULL,
                'time' => NULL,
                'year' => NULL,
                'ip' => NULL,
                'mark' => NULL,
                'entity' => array(
                    'id' => NULL,
                    'text' => NULL,
                    'collection' => array(),
                    'entity' => NULL,
                    'publicProperty' => 'defaultValue'
                ),
                'collection' => array(
                    array(
                        'id' => NULL,
                        'text' => 'foo',
                        'collection' => array(),
                        'entity' => NULL,
                        'publicProperty' => 'defaultValue'
                    ),
                ),
                'manyToMany' => array(array('id' => 1, 'manyToManyNoDominance' => array(), 'text' => NULL)),
                'manyToOne' => NULL,
                'oneToOne' => NULL,
                'readonly' => NULL,
                'storedData' => NULL,
                'enumeration' => NULL,
                'publicProperty' => 'defaultValue',
            ),
            $this->entity->toArray(true)
        );
    }

    public function testGetData()
    {
        $this->entity->empty = null;
        Assert::same(
            ['text' => 'test', 'id' => 1, 'empty' => NULL],
            $this->entity->getData()
        );
    }

    public function testJsonSerializable()
    {
        Assert::same(
            '{"id":1,"text":"test","empty":"","url":null,"email":null,"time":null,"year":null,"ip":null,"mark":null,"entity":null,"collection":[],"manyToMany":[],"manyToOne":null,"oneToOne":null,"readonly":null,"storedData":null,"enumeration":null,"publicProperty":"defaultValue"}',
            json_encode($this->entity)
        );
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected integer but string given on property id!
     */
    public function testSetPropertyWithInvalidType()
    {
        $this->entity->id = "invalidType";
    }

    public function testQuery()
    {
        Assert::type("UniMapper\Query\Select", Fixtures\Entity\Simple::query()->select());
    }

    public function testSerializable()
    {
        $serialized = 'C:38:"UniMapper\Tests\Fixtures\Entity\Simple":102:{a:4:{s:4:"text";s:4:"test";s:2:"id";i:1;s:5:"empty";s:0:"";s:14:"publicProperty";s:12:"defaultValue";}}';
        Assert::same($serialized, serialize($this->entity));

        $unserialized = unserialize($serialized);
        Assert::isEqual($this->entity, $unserialized);
        Assert::type("UniMapper\Reflection\Entity", $unserialized->getReflection());
        Assert::same(['text' => 'test', 'id' => 1, 'empty' => ''], $unserialized->getData());
    }

    public function testImport()
    {
        $entityObject = new stdClass;
        $entityObject->text = "foo";
        $entityObject->publicProperty = "foo";

        $this->entity->import(
            [
                "id" => "2",
                "text" => 3.0,
                "collection" => [],
                "time" => "1999-01-12",
                "publicProperty" => "foo",
                "empty" => null,
                "entity" => $entityObject,
                "collection" => [$entityObject]
            ]
        );
        Assert::same(2, $this->entity->id);
        Assert::same("3", $this->entity->text);
        Assert::type("UniMapper\EntityCollection", $this->entity->collection);
        Assert::same("1999-01-12", $this->entity->time->format("Y-m-d"));
        Assert::same("foo", $this->entity->publicProperty);
        Assert::same(null, $this->entity->empty);
        Assert::same("foo", $this->entity->entity->text);
        Assert::same("foo", $this->entity->entity->publicProperty);
        Assert::same("foo", $this->entity->collection[0]->publicProperty);
        Assert::same("foo", $this->entity->collection[0]->text);
        Assert::same(1, count($this->entity->collection));

        $this->entity->import(["time" => ["date" => "1999-02-12"]]);
        Assert::same("1999-02-12", $this->entity->time->format("Y-m-d"));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values must be traversable data!
     */
    public function testImportInvalidArgument()
    {
        $this->entity->import(null);
    }

    /**
     * @throws Exception Can not convert value on property 'collection' automatically!
     */
    public function testImportInvalidCollection()
    {
        $this->entity->import(["collection" => "foo"]);
    }

    /**
     * @throws Exception Can not convert value on property 'time' automatically!
     */
    public function testImportInvalidDateTime()
    {
        $this->entity->import(["time" => []]);
    }

    public function testImportSkippedAutomatically()
    {
        $this->entity->import(
            [
                "readonly" => "foo",
                "undefined" => "foo",
                "year" => 1999
            ]
        );
        Assert::null($this->entity->readonly);
        Assert::null($this->entity->year);
    }

    public function testGetComputedProperty()
    {
        Assert::null($this->entity->year);
        $this->entity->time = new DateTime;
        Assert::same((int) date("Y"), $this->entity->year);
    }

    /**
     * @throws UniMapper\Exception\PropertyException Computed property is read-only!
     */
    public function testSetComputedProperty()
    {
        $this->entity->year = 1999;
    }

    public function testIterate()
    {
        $expected = [
            'id',
            'text',
            'empty',
            'url',
            'email',
            'time',
            'year',
            'ip',
            'mark',
            'entity',
            'collection',
            'manyToMany',
            'manyToOne',
            'oneToOne',
            'readonly',
            'storedData',
            'enumeration',
            'publicProperty'
        ];

        $given = [];
        foreach ($this->entity as $name => $value) {
           $given[] = $name;
        }
        Assert::same($expected, $given);
        Assert::same('publicProperty', key($this->entity));
        Assert::same('defaultValue', current($this->entity));
    }

    public function testCall()
    {
        Assert::type("UniMapper\EntityCollection", $this->entity->manyToMany());
        Assert::same("Remote", $this->entity->manyToMany()->getEntityReflection()->getName());
        Assert::type("UniMapper\Tests\Fixtures\Entity\Remote", $this->entity->manyToOne());
        Assert::type("UniMapper\Tests\Fixtures\Entity\Remote", $this->entity->oneToOne());
    }

    public function testAttach()
    {
        $this->entity->attach();
        Assert::same(Fixtures\Entity\Simple::CHANGE_ATTACH, $this->entity->getChangeType());
    }

    /**
     * @throws UniMapper\Exception\PropertyAccessException Undefined property 'undefined'!
     */
    public function testCallUndefinedProperty()
    {
        $this->entity->undefined();
    }

    /**
     * @throws UniMapper\Exception\PropertyAccessException Only properties with type entity or collection can call changes!
     */
    public function testCallInvalidPropertyType()
    {
        $this->entity->id();
    }

}

$testCase = new EntityTest;
$testCase->run();