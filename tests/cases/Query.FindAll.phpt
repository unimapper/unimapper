<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$collection = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple");
$collection[] = new Fixtures\Entity\Simple;
$collection[] = new Fixtures\Entity\Simple;

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");

$mapperMock->expects("findAll")->once()->andReturn($collection);
$query = new \UniMapper\Query\FindAll(new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$result = $query->execute();

Assert::type("Unimapper\EntityCollection", $result);
Assert::same(2, count($result));
foreach ($result as $entity) {
    Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
    Assert::true($entity->isActive());
}