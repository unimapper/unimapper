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
    array('id' => 1, 'text' => 'test', 'empty' => NULL, 'time' => NULL, 'entity' => NULL, 'collection' => $entityArray["collection"]),
    $entityArray
);

// getData()
Assert::same(
    array('text' => 'test', 'id' => 1),
    $entity->getData()
);

// JsonSerializable
Assert::same('{"id":1,"text":"test","empty":null,"time":null,"entity":null,"collection":[]}', json_encode($entity));

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyTypeException", "Expected integer but string given!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyUndefinedException", "Undefined property with name 'undefined'!");

// Serializable
$serialized = 'C:38:"UniMapper\Tests\Fixtures\Entity\Simple":41:{a:2:{s:4:"text";s:4:"test";s:2:"id";i:1;}}';
Assert::same($serialized, serialize($entity));
Assert::isEqual($entity, unserialize($serialized));