<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$mapper = new Fixtures\Mapper\Simple("FirstMapper");

// Get name
Assert::same("FirstMapper", $mapper->getName());

// Mapper not defined in entity
Assert::exception(function() use ($mapper) {
    $entity = new Fixtures\Entity\NoMapper;
    $mapper->getResource($entity->getReflection());
}, "UniMapper\Exceptions\MapperException", "Entity does not define mapper with name FirstMapper!");

// Get resource
$entity = new Fixtures\Entity\Simple;
Assert::same("first_resource", $mapper->getResource($entity->getReflection()));