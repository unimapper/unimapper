<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class EntityTest extends Tester\TestCase
{

    /** @var \Mockista\Mock */
    private $mapperMock;

    /** @var \UniMapper\Tests\Fixtures\Entity\Simple */
    private $entity;

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");

        $this->entity = new Fixtures\Entity\Simple;
        $this->entity->text = "test";
        $this->entity->id = 1;
        $this->entity->empty = "";
    }

    public function testLocalProperties()
    {
        Assert::same("defaultValue", $this->entity->localProperty);

        $this->entity->localProperty = "newValue";
        Assert::same("newValue", $this->entity->localProperty);
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
        Assert::true(isset($this->entity->localProperty));
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
        $nestedEntity = new Fixtures\Entity\Nested;
        $nestedEntity->text = "foo";
        $this->entity->collection[] = $nestedEntity;

        Assert::same(
            ['id' => 1, 'text' => 'test', 'empty' => '', 'url' => NULL, 'email' => NULL, 'time' => NULL, 'year' => NULL, 'ip' => NULL, 'mark' => NULL, 'entity' => NULL, 'collection' => $this->entity->collection, 'readonly' => NULL, 'localProperty' => 'defaultValue'],
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
                'entity' => NULL,
                'collection' => array(
                    array(
                        'id' => NULL,
                        'text' => 'foo',
                        'collection' => array(),
                        'entity' => NULL,
                    ),
                ),
                'readonly' => NULL,
                'localProperty' => 'defaultValue',
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
            '{"id":1,"text":"test","empty":"","url":null,"email":null,"time":null,"year":null,"ip":null,"mark":null,"entity":null,"collection":[],"readonly":null,"localProperty":"defaultValue"}',
            json_encode($this->entity)
        );
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Expected integer but string given on property id!
     */
    public function testInvalidPropertyType()
    {
        $this->entity->id = "invalidType";
    }

    /**
     * @throws UniMapper\Exceptions\PropertyUndefinedException Undefined property with name 'undefined'!
     */
    public function testUndefinedProperty()
    {

        $this->entity->undefined;
    }


    public function testSerializable()
    {
        $serialized = 'C:38:"UniMapper\Tests\Fixtures\Entity\Simple":101:{a:4:{s:4:"text";s:4:"test";s:2:"id";i:1;s:5:"empty";s:0:"";s:13:"localProperty";s:12:"defaultValue";}}';
        Assert::same($serialized, serialize($this->entity));

        $unserialized = unserialize($serialized);
        Assert::isEqual($this->entity, $unserialized);
        Assert::type("UniMapper\Reflection\Entity", $unserialized->getReflection());
        Assert::same(['text' => 'test', 'id' => 1, 'empty' => ''], $unserialized->getData());
    }

    public function testImport()
    {
        $this->entity->import(
            [
                "id" => "2",
                "text" => 3.0,
                "collection" => [],
                "time" => "1999-01-12",
                "localProperty" => "foo",
                "empty" => null
            ]
        );
        Assert::same(2, $this->entity->id);
        Assert::same("3", $this->entity->text);
        Assert::type("UniMapper\EntityCollection", $this->entity->collection);
        Assert::same("1999-01-12", $this->entity->time->format("Y-m-d"));
        Assert::same("foo", $this->entity->localProperty);
        Assert::same(null, $this->entity->empty);

        $this->entity->import(["time" => ["date" => "1999-02-12"]]);
        Assert::same("1999-02-12", $this->entity->time->format("Y-m-d"));
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

    /**
     * @throws Exception Undefined property with name 'undefined'!
     */
    public function testImportUndefined()
    {
        $this->entity->import(["undefined" => "foo"]);
    }

    public function testImportReadonly()
    {
        $this->entity->import(["readonly" => "foo"]);
    }

    public function testIsActive()
    {
        Assert::false($this->entity->isActive());

        $this->entity->setActive($this->mapperMock);
        Assert::true($this->entity->isActive());
    }

    /**
     * @throws Exception Entity is not active!
     */
    public function testSaveNotActive()
    {
        $this->entity->save();
    }

    public function testSaveUpdate()
    {
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo", "id" => 1]);
        $this->mapperMock->expects("updateOne")->once()->with("resource", "id", 1,["text" => "foo", "id" => 1]);
        $this->mapperMock->freeze();

        $this->entity->setActive($this->mapperMock);
        $this->entity->save();
    }

    public function testSaveInsert()
    {
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo", "id" => 1]);
        $this->mapperMock->expects("insert")->once()->with("resource", ["text" => "foo", "id" => 1])->andReturn(["id" => 1]);
        $this->mapperMock->expects("mapValue")->once()->andReturn(1);
        $this->mapperMock->freeze();

        $this->entity->id = null;
        $this->entity->setActive($this->mapperMock);
        $this->entity->save();
        Assert::same(1, $this->entity->id);
    }

    /**
     * @throws Exception Entity is not active!
     */
    public function testDeletNotActive()
    {
        $this->entity->delete();
    }

    /**
     * @throws Exception Primary value must be set!
     */
    public function testDeletNoPrimaryValue()
    {
        unset($this->entity->id);
        $this->entity->setActive($this->mapperMock);
        $this->entity->delete();
    }

    public function testDelete()
    {
        $this->mapperMock->expects("delete")->with("resource", [["id", "=", 1, "AND"]])->once();
        $this->mapperMock->freeze();

        $this->entity->setActive($this->mapperMock);
        Assert::null($this->entity->delete());
    }

    /**
     * @throws Exception Value foo is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateEmail on property email!
     */
    public function testValidateEmail()
    {
        $this->entity->email = "john.doe@example.com";
        $this->entity->email = "foo";
    }

    /**
     * @throws Exception Value example.com is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateUrl on property url!
     */
    public function testValidateUrl()
    {
        $this->entity->url = "http://www.example.com";
        $this->entity->url = "example.com";
    }

    /**
     * @throws Exception Value 255.255.255.256 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateIp on property ip!
     */
    public function testValidateIp()
    {
        $this->entity->ip = "192.168.0.1";
        $this->entity->ip = "255.255.255.256";
    }

    /**
     * @throws Exception Value 6 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateMark on property mark!
     */
    public function testValidate()
    {
        $this->entity->mark = 1;
        $this->entity->mark = 6;
    }

    public function testComputed()
    {
        Assert::null($this->entity->year);
        $this->entity->time = new DateTime;
        Assert::same((int) date("Y"), $this->entity->year);
    }

    /**
     * @throws UniMapper\Exceptions\PropertyException Can not set computed property with name 'year'!
     */
    public function testComputedSet()
    {
        $this->entity->year = 1999;
    }

}

$testCase = new EntityTest;
$testCase->run();