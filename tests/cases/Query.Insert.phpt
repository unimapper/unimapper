<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

// Simple entity
$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $mapperMock;

$entity = new Fixtures\Entity\Simple;

$query = new \UniMapper\Query\Insert($entity->getReflection(), $mappers, $entity);

$result = $query->execute();

Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
Assert::same(1, $result->id);
Assert::true($result->isActive());