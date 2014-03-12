<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$entity = new Entity;
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
Assert::same(
    array('id' => 1, 'text' => 'test', 'empty' => NULL, 'entity' => NULL, 'collection' => $entity->toArray()["collection"]),
    $entity->toArray()
);

// getData()
Assert::same(
    array('text' => 'test', 'id' => 1),
    $entity->getData()
);

// JsonSerializable
Assert::same('{"id":1,"text":"test","empty":null,"entity":null,"collection":[]}', json_encode($entity));

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyAccessException", "Expected integer but string given!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyAccessException", "Undefined property with name 'undefined'!");