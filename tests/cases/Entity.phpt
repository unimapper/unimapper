<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;


require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->text = "test";
$entity->id = 1;

Assert::type("UniMapper\Entity", $entity);

// isset()
Assert::true(isset($entity->id));
Assert::false(isset($entity->missing));

// empty()
Assert::true(empty($entity->missing));
Assert::false(empty($entity->id));

// unset()
unset($entity->id);
Assert::null($entity->id);

// Valid property
$entity->id = 1;
Assert::equal(1, $entity->id);

// toArray()
$entityArray = $entity->toArray();
Assert::same(
    ['id' => 1, 'text' => 'test', 'empty' => NULL, 'url' => NULL, 'email' => NULL, 'time' => NULL, 'year' => NULL, 'ip' => NULL, 'mark' => NULL, 'entity' => NULL, 'collection' => $entityArray["collection"]],
    $entityArray
);

// getData()
Assert::same(
    array('text' => 'test', 'id' => 1),
    $entity->getData()
);

// JsonSerializable
Assert::same('{"id":1,"text":"test","empty":null,"url":null,"email":null,"time":null,"year":null,"ip":null,"mark":null,"entity":null,"collection":[]}', json_encode($entity));

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyTypeException", "Expected integer but string given on property id!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyUndefinedException", "Undefined property with name 'undefined'!");

// Serializable
$serialized = 'C:38:"UniMapper\Tests\Fixtures\Entity\Simple":41:{a:2:{s:4:"text";s:4:"test";s:2:"id";i:1;}}';
Assert::same($serialized, serialize($entity));
Assert::isEqual($entity, unserialize($serialized));

// create()
Assert::type(
    "UniMapper\Tests\Fixtures\Entity\Simple",
    Fixtures\Entity\Simple::create(
        array("text" => "foo", "collection" => array())
    )
);
Assert::exception(function() {
    Fixtures\Entity\Simple::create(array("collection" => "foo"));
}, "Exception", "Can not set value automatically!");

// m:validate email
Assert::type(
    "UniMapper\Tests\Fixtures\Entity\Simple",
    Fixtures\Entity\Simple::create(["email" => "john.doe@example.com"])
);
Assert::exception(function() {
    Fixtures\Entity\Simple::create(["email" => "foo"]);
}, "Exception", "Value foo is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateEmail on property email!");

// m:validate url
Assert::type(
    "UniMapper\Tests\Fixtures\Entity\Simple",
    Fixtures\Entity\Simple::create(["url" => "http://www.example.com"])
);
Assert::exception(function() {
    Fixtures\Entity\Simple::create(["url" => "example.com"]);
}, "Exception", "Value example.com is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateUrl on property url!");

// m:validate ip
Assert::type(
    "UniMapper\Tests\Fixtures\Entity\Simple",
    Fixtures\Entity\Simple::create(["ip" => "192.168.0.1"])
);
Assert::exception(function() {
    Fixtures\Entity\Simple::create(["ip" => "255.255.255.256"]);
}, "Exception", "Value 255.255.255.256 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateIp on property ip!");

// m:validate mark
Assert::type(
    "UniMapper\Tests\Fixtures\Entity\Simple",
    Fixtures\Entity\Simple::create(["mark" => 1])
);
Assert::exception(function() {
    Fixtures\Entity\Simple::create(["mark" => 6]);
}, "Exception", "Value 6 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateMark on property mark!");

// m:computed
$computedEntity = new Fixtures\Entity\Simple;
Assert::same(null, $computedEntity->year);
$computedEntity->time = new DateTime;
Assert::same((int) date("Y"), $computedEntity->year);
Assert::exception(function() use ($computedEntity) {
   $computedEntity->year = 1999;
}, "UniMapper\Exceptions\PropertyException", "Can not set computed property with name 'year'!");