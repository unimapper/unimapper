<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

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

    public function testPublicProperties()
    {
        Assert::same("defaultValue", $this->entity->publicProperty);

        $this->entity->publicProperty = "newValue";
        Assert::same("newValue", $this->entity->publicProperty);
    }

    /**
     * @throws Exception Property 'readonly' is not writable!
     */
    public function testReadnonly()
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

    public function testValidProperty()
    {
        $this->entity->id = 1;
        Assert::equal(1, $this->entity->id);
    }

    public function testToArray()
    {
        $nestedEntity = $this->createEntity("Nested", ["text" => "foo"]);

        $this->entity->collection[] = $nestedEntity;
        $this->entity->manyToMany[] = $this->createEntity(
            "Remote",
            ["id" => 1]
        );
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
                'entity' => $this->entity->entity,
                'collection' => $this->entity->collection,
                'manyToMany' => $this->entity->manyToMany,
                'manyToOne' => $this->entity->manyToOne,
                'oneToOne' => $this->entity->oneToOne,
                'readonly' => NULL,
                'storedData' => NULL,
                'publicProperty' => 'defaultValue',
            ),
            $this->entity->toArray()
        );

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
                'manyToMany' => array(array('id' => 1, 'manyToManyNoDominance' => array())),
                'manyToOne' => NULL,
                'oneToOne' => NULL,
                'readonly' => NULL,
                'storedData' => NULL,
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
            '{"id":1,"text":"test","empty":"","url":null,"email":null,"time":null,"year":null,"ip":null,"mark":null,"entity":null,"collection":[],"manyToMany":[],"manyToOne":null,"oneToOne":null,"readonly":null,"storedData":null,"publicProperty":"defaultValue"}',
            json_encode($this->entity)
        );
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected integer but string given on property id!
     */
    public function testInvalidPropertyType()
    {
        $this->entity->id = "invalidType";
    }

    /**
     * @throws UniMapper\Exception\PropertyAccessException Undefined property 'undefined'!
     */
    public function testUndefinedProperty()
    {

        $this->entity->undefined;
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

    public function testComputed()
    {
        Assert::null($this->entity->year);
        $this->entity->time = new DateTime;
        Assert::same((int) date("Y"), $this->entity->year);
    }

    /**
     * @throws UniMapper\Exception\PropertyException Can not set computed property 'year'!
     */
    public function testComputedSet()
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

    public function testEntityWithoutProperties()
    {
        Assert::count(
            0,
            $this->createEntity("NoProperty")->getReflection()->getProperties()
        );
    }

}

$testCase = new EntityTest;
$testCase->run();