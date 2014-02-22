<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mockista = new \Mockista\Registry;

$firstMapperMock = $mockista->create("TestMapper");
$firstMapperMock->expects("insert")->once()->andReturn(1);

$secondMapperMock = $mockista->create("TestMapper");
$secondMapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $firstMapperMock;
$mappers["SecondMapper"] = $secondMapperMock;

$entity = new HybridEntity;

$query = new \UniMapper\Query\Insert($entity->getReflection(), $mappers, $entity);

$resultEntity = $query->execute();

Assert::type("HybridEntity", $resultEntity);
Assert::same(1, $resultEntity->id);
Assert::same(true, $resultEntity->isActive());