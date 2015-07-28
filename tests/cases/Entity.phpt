<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityTest extends \Tester\TestCase
{

    /** @var \UniMapper\Tests\Fixtures\Entity\Simple */
    private $entity;

    public function setUp()
    {
        $this->entity = new Fixtures\Entity\Simple(
            [
                "text" => "test",
                "id" => 1,
                "empty" => ""
            ]
        );
    }

    public function testConstruct()
    {
        $entity = new Fixtures\Entity\Simple(
            [
                "year" => "foo", // Skip comuted
                "readonly" => "foo", // Set readonly
                "undefined" => "foo", // Skip undefined,
                "id" => "1", // Convert type automatically,
                "publicProperty" => "foo" // Set public property
            ]
        );

        Assert::null($entity->year);
        Assert::same("foo", $entity->readonly);
        Assert::same(1, $entity->id);
        Assert::same("foo", $entity->publicProperty);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Only scalar variables can be converted to basic type!
     */
    public function testConstructNotAbleToConvertType()
    {
        new Fixtures\Entity\Simple(["id" => new DateTime]);
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
     * @throws UniMapper\Exception\InvalidArgumentException Undefined property 'undefined'!
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
     * @throws UniMapper\Exception\InvalidArgumentException Property 'readonly' is read-only!
     */
    public function testSetReadonly()
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

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Property 'readonly' is read-only!
     */
    public function testUnsetReadonlyProperty()
    {
        unset($this->entity->readonly);
    }

    public function testSetProperty()
    {
        $this->entity->id = 1;
        Assert::equal(1, $this->entity->id);
        $this->entity->collection[] = new Fixtures\Entity\Nested(["text" => "foo"]);
        Assert::same("foo", $this->entity->collection[0]->text);
    }

    public function testToArray()
    {
        $this->entity->collection[] = new Fixtures\Entity\Nested(["text" => "foo"]);
        $this->entity->manyToMany[] = new Fixtures\Entity\Remote(["id" => 1]);
        $this->entity->entity = new Fixtures\Entity\Nested;

        Assert::type("array", $this->entity->toArray());
        Assert::count(23, $this->entity->toArray());
        Assert::same("test", $this->entity->toArray()["text"]);
        Assert::same("", $this->entity->toArray()["empty"]);
        Assert::same($this->entity->collection, $this->entity->toArray()["collection"]);
        Assert::same($this->entity->entity, $this->entity->toArray()["entity"]);
        Assert::same("defaultValue", $this->entity->toArray()["publicProperty"]);
        Assert::same($this->entity->manyToMany, $this->entity->toArray()["manyToMany"]);
    }

    public function testToArrayRecursive()
    {
        $this->entity->collection[] = new Fixtures\Entity\Nested(["text" => "foo"]);
        $this->entity->manyToMany[] = new Fixtures\Entity\Remote(["id" => 1]);
        $this->entity->entity = new Fixtures\Entity\Nested;

        Assert::same(
            array(
                'id' => 1,
                'text' => 'test',
                'empty' => '',
                'url' => NULL,
                'email' => NULL,
                'time' => NULL,
                'date' => NULL,
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
                'oneToMany' => array(),
                'oneToManyRemote' => array(),
                'manyToMany' => array(array('id' => 1, 'manyToManyNoDominance' => array(), 'text' => NULL)),
                'mmFilter' => array(),
                'manyToOne' => NULL,
                'oneToOne' => NULL,
                'ooFilter' => NULL,
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
        $this->entity->time = new DateTime("2014-01-01");
        $this->entity->date = new DateTime("2014-01-01");
        Assert::same(
            '{"id":1,"text":"test","empty":"","url":null,"email":null,"time":{"date":"2014-01-01 00:00:00.000000","timezone_type":3,"timezone":"Europe\/Prague"},"date":{"date":"2014-01-01","timezone_type":3,"timezone":"Europe\/Prague"},"year":2014,"ip":null,"mark":null,"entity":null,"collection":[],"oneToMany":[],"oneToManyRemote":[],"manyToMany":[],"mmFilter":[],"manyToOne":null,"oneToOne":null,"ooFilter":null,"readonly":null,"storedData":null,"enumeration":null,"publicProperty":"defaultValue"}',
            json_encode($this->entity)
        );
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected integer but string given on property id!
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
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $unserialized);
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
                "collection" => [$entityObject],
                "readonly" => "foo",
                "undefined" => "foo",
                "year" => 2000
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
        Assert::same(1999, $this->entity->year);
        Assert::null($this->entity->readonly);

        $this->entity->import(["time" => ["date" => "1999-02-12"]]);
        Assert::same("1999-02-12", $this->entity->time->format("Y-m-d"));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values must be traversable data!
     */
    public function testImportInvalidArgumentNotTraversable()
    {
        $this->entity->import(null);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Can not convert value on property 'collection' automatically!
     */
    public function testImportInvalidArgumentCollection()
    {
        $this->entity->import(["collection" => "foo"]);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Can not convert value on property 'time' automatically!
     */
    public function testImportInvalidArgumentDateTime()
    {
        $this->entity->import(["time" => []]);
    }

    public function testGetComputedProperty()
    {
        Assert::null($this->entity->year);
        $this->entity->time = new DateTime;
        Assert::same((int) date("Y"), $this->entity->year);
    }

    public function testGetChanges()
    {
        $entity = new Fixtures\Entity\Remote(["id" => 1]);

        $this->entity->manyToMany()->attach($entity);
        $this->entity->manyToMany()->add($entity);
        $this->entity->manyToMany()->detach($entity);

        $this->entity->manyToOne()->id = 2;
        $this->entity->manyToOne()->attach();

        $this->entity->oneToOne()->id = 3;
        $this->entity->oneToOne()->detach();

        Assert::same(
            [
                "manyToMany" => $this->entity->manyToMany(),
                "manyToOne" => $this->entity->manyToOne(),
                "oneToOne" => $this->entity->oneToOne()
            ],
            $this->entity->getChanges()
        );
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Computed property is read-only!
     */
    public function testSetComputedProperty()
    {
        $this->entity->year = 1999;
    }

    public function testIterate()
    {
        $expected = array(
            'id',
            'text',
            'empty',
            'url',
            'email',
            'time',
            'date',
            'year',
            'ip',
            'mark',
            'entity',
            'collection',
            'oneToMany',
            'oneToManyRemote',
            'manyToMany',
            'mmFilter',
            'manyToOne',
            'oneToOne',
            'ooFilter',
            'readonly',
            'storedData',
            'enumeration',
            'publicProperty',
        );

        $given = [];
        foreach ($this->entity as $name => $value) {
           $given[] = $name;
        }
        Assert::same($expected, $given);
        Assert::same('publicProperty', key($this->entity));
        Assert::same('defaultValue', current($this->entity));
    }

    public function testCallOnCollection()
    {
        $collection = new UniMapper\EntityCollection("Remote");

        Assert::same($collection, $this->entity->manyToMany($collection));
        Assert::same($collection, $this->entity->manyToMany());
        Assert::same($collection, $this->entity->manyToMany(null));
        Assert::notSame($collection, $this->entity->manyToMany(false));
    }

    public function testCallOnEntity()
    {
        $entity = new Fixtures\Entity\Remote;

        Assert::same($entity, $this->entity->manyToOne($entity));
        Assert::same($entity, $this->entity->manyToOne());
        Assert::same($entity, $this->entity->manyToOne(null));
        Assert::notSame($entity, $this->entity->manyToOne(false));
    }

    public function testAttach()
    {
        $this->entity->attach();
        Assert::same(Fixtures\Entity\Simple::CHANGE_ATTACH, $this->entity->getChangeType());
    }

    public function testAdd()
    {
        $this->entity->add();
        Assert::same(Fixtures\Entity\Simple::CHANGE_ADD, $this->entity->getChangeType());
    }

    public function testDetach()
    {
        $this->entity->detach();
        Assert::same(Fixtures\Entity\Simple::CHANGE_DETACH, $this->entity->getChangeType());
    }

    public function testRemove()
    {
        $this->entity->remove();
        Assert::same(Fixtures\Entity\Simple::CHANGE_REMOVE, $this->entity->getChangeType());
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Undefined property 'undefined'!
     */
    public function testCallUndefinedProperty()
    {
        $this->entity->undefined();
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Only properties with type entity or collection can call changes!
     */
    public function testCallInvalidPropertyType()
    {
        $this->entity->id();
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException You must pass instance of entity collection!
     */
    public function testCallInvalidArgumentType()
    {
        $this->entity->manyToMany("");
    }

}

$testCase = new EntityTest;
$testCase->run();