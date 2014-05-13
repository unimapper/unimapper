<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;


require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->text = "test";
$entity->id = 1;

// local properties
Assert::same("defaultValue", $entity->localProperty);

// isset()
Assert::true(isset($entity->id));
Assert::true(isset($entity->localProperty));
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

// Local property
$entity->localProperty = "newValue";

// toArray()
$entityArray = $entity->toArray();
Assert::same(
    ['id' => 1, 'text' => 'test', 'empty' => NULL, 'url' => NULL, 'email' => NULL, 'time' => NULL, 'year' => NULL, 'ip' => NULL, 'mark' => NULL, 'entity' => NULL, 'collection' => $entityArray["collection"], 'localProperty' => 'newValue'],
    $entityArray
);

// getData()
Assert::same(
    array('text' => 'test', 'id' => 1),
    $entity->getData()
);

// JsonSerializable
Assert::same('{"id":1,"text":"test","empty":null,"url":null,"email":null,"time":null,"year":null,"ip":null,"mark":null,"entity":null,"collection":[],"localProperty":"newValue"}', json_encode($entity));

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyTypeException", "Expected integer but string given on property id!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyUndefinedException", "Undefined property with name 'undefined'!");

// Serializable
$serialized = 'C:38:"UniMapper\Tests\Fixtures\Entity\Simple":77:{a:3:{s:4:"text";s:4:"test";s:2:"id";i:1;s:13:"localProperty";s:8:"newValue";}}';
Assert::same($serialized, serialize($entity));
$unserialized = unserialize($serialized);
Assert::isEqual($entity, $unserialized);
Assert::type("UniMapper\Reflection\Entity", $unserialized->getReflection());

// import()
$entity->import(["id" => "2", "text" => 3.0, "collection" => [], "time" => "1999-01-12", "localProperty" => "foo"]);
Assert::same(2, $entity->id);
Assert::same("3", $entity->text);
Assert::type("UniMapper\EntityCollection", $entity->collection);
Assert::same("1999-01-12", $entity->time->format("Y-m-d"));
Assert::same("foo", $entity->localProperty);
$entity->import(["time" => ["date" => "1999-02-12"]]);
Assert::same("1999-02-12", $entity->time->format("Y-m-d"));
Assert::exception(function() use ($entity) {
    $entity->import(["collection" => "foo"]);
}, "Exception", "Can not set value on property 'collection' automatically!");
Assert::exception(function() use ($entity) {
    $entity->import(["time" => []]);
}, "Exception", "Can not set value on property 'time' automatically!");
Assert::exception(function() use ($entity) {
    $entity->import(["undefined" => "foo"]);
}, "UniMapper\Exceptions\PropertyUndefinedException", "Undefined property with name 'undefined'!");

// m:validate email
$entity->email = "john.doe@example.com";
Assert::exception(function() use ($entity) {
    $entity->email = "foo";
}, "Exception", "Value foo is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateEmail on property email!");

// m:validate url
$entity->url = "http://www.example.com";
Assert::exception(function() use ($entity) {
    $entity->url = "example.com";
}, "Exception", "Value example.com is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateUrl on property url!");

// m:validate ip
$entity->ip = "192.168.0.1";
Assert::exception(function() use ($entity) {
    $entity->ip = "255.255.255.256";
}, "Exception", "Value 255.255.255.256 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateIp on property ip!");

// m:validate mark
$entity->mark = 1;
Assert::exception(function() use ($entity) {
    $entity->mark = 6;
}, "Exception", "Value 6 is not valid for UniMapper\Tests\Fixtures\Entity\Simple::validateMark on property mark!");

// m:computed
$computedEntity = new Fixtures\Entity\Simple;
Assert::same(null, $computedEntity->year);
$computedEntity->time = new DateTime;
Assert::same((int) date("Y"), $computedEntity->year);
Assert::exception(function() use ($computedEntity) {
   $computedEntity->year = 1999;
}, "UniMapper\Exceptions\PropertyException", "Can not set computed property with name 'year'!");