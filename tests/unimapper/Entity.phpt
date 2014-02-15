<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @property integer $id
 */
class Entity extends \UniMapper\Entity
{

}

$entity = new Entity;

Assert::type("UniMapper\Entity", $entity);

// Valid property
$entity->id = 1;
Assert::equal(1, $entity->id);

// Invalid property type
Assert::exception(function() use ($entity) {
    $entity->id = "invalidType";
}, "UniMapper\Exceptions\PropertyAccessException", "Expected integer but string given!");

// Property not exists
Assert::exception(function() use ($entity) {
    $entity->undefined;
}, "UniMapper\Exceptions\PropertyAccessException", "Undefined property with name 'undefined'!");