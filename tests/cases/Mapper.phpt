<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$mapper = new Fixtures\Mapper\Simple("FooMapper");
$entity = new Fixtures\Entity\Simple;

// Get name
Assert::same("FooMapper", $mapper->getName());

// getResource() Mapper not defined in entity
Assert::exception(function() use ($entity) {

    $mapper = new Fixtures\Mapper\Simple("UndefinedMapper");
    $mapper->getResource($entity->getReflection());
}, "UniMapper\Exceptions\MapperException", "Entity does not define mapper with name UndefinedMapper!");
Assert::same("resource", $mapper->getResource($entity->getReflection()));

$email = "john.doe@example.com";
$url = "http://example.com";
$entity->localProperty = "foo";
$entity->email = $email;
$entity->url = $url;
$entity->empty = null;

// mapEntity()
Assert::isEqual($entity, $mapper->mapEntity("UniMapper\Tests\Fixtures\Entity\Simple", ["email_address" => $email, "localProperty" => "foo", "undefined" => 1, "link" => $url]));

// unmapEntity()
Assert::same(["email_address" => $email, "link" => $url, 'empty' => null], $mapper->unmapEntity($entity));

// mapCollection()
$collection = $mapper->mapCollection("UniMapper\Tests\Fixtures\Entity\Simple", [["email_address" => $email, "localProperty" => "foo", "undefined" => 1, "link" => $url]]);
Assert::type("UniMapper\EntityCollection", $collection);
Assert::isEqual($entity, $collection[0]);

// unmapCollection()
Assert::same([['email_address' => $email, 'link' => $url]], $mapper->unmapCollection($collection));