<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapper = new TestMapper("FirstMapper");

// Get name
Assert::same("FirstMapper", $mapper->getName());

// Mapper not defined in entity
Assert::exception(function() use ($mapper) {
    $entity = new NoMapperEntity;
    $mapper->getResource($entity->getReflection());
}, "UniMapper\Exceptions\MapperException", "Entity does not define mapper with name FirstMapper!");

// Get resource
$entity = new Entity;
Assert::same("first_resource", $mapper->getResource($entity->getReflection()));