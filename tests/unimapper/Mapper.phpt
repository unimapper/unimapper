<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/TestMapper.php';

class NoMapperEntity extends \UniMapper\Entity
{}

/**
 * @mapper myTestMapper(resource_name)
 *
 * @property integer $id
 * @property string  $text
 */
class Entity extends \UniMapper\Entity
{}

$mapper = new TestMapper("myTestMapper");

// Get name
Assert::same("myTestMapper", $mapper->getName());

// Mapper not defined in entity
Assert::exception(function() use ($mapper) {
    $entity = new NoMapperEntity;
    $mapper->getResource($entity->getReflection());
}, "UniMapper\Exceptions\MapperException", "Entity does not define mapper with name myTestMapper!");

// Get resource
$entity = new Entity;
Assert::same("resource_name", $mapper->getResource($entity->getReflection()));