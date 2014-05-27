<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$mapper = new Fixtures\Mapper\Simple("FooMapper");

// Get name
Assert::same("FooMapper", $mapper->getName());

$email = "john.doe@example.com";
$url = "http://example.com";
$entity = new Fixtures\Entity\Simple;
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