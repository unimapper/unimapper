<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @property integer $id
 * @property string  $text
 * @property string  $empty
 */
class Entity extends \UniMapper\Entity
{}

$entity = new Entity;
$entity->text = "test";
$entity->id = 1;

Assert::type("UniMapper\Entity", $entity);

// Valid property
Assert::equal(1, $entity->id);

// toArray()
Assert::same(array('text' => 'test', 'id' => 1), $entity->toArray());

// JsonSerializable
Assert::same('{"text":"test","id":1}', json_encode($entity));

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyAccessException", "Expected integer but string given!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyAccessException", "Undefined property with name 'undefined'!");