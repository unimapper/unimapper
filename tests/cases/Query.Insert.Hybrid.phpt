<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$firstMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$firstMapperMock->expects("insert")->once()->andReturn(1);

$secondMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$secondMapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $firstMapperMock;
$mappers["SecondMapper"] = $secondMapperMock;

$entity = new Fixtures\Entity\Hybrid;

$query = new \UniMapper\Query\Insert($entity->getReflection(), $mappers, $entity);

Assert::true($query->returnPrimaryValue);

$resultEntity = $query->execute();

Assert::type("UniMapper\Tests\Fixtures\Entity\Hybrid", $resultEntity);
Assert::same(1, $resultEntity->id);
Assert::false($query->returnPrimaryValue);