<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// Simple entity
$mapperMock = $mockista->create("TestMapper");
$mapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $mapperMock;

$entity = new Entity;

$query = new \UniMapper\Query\Insert($entity->getReflection(), $mappers, $entity);

$resultEntity = $query->execute();

Assert::type("Entity", $resultEntity);
Assert::same(1, $resultEntity->id);