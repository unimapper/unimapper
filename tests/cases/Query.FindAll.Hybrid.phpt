<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';



$firstMapperMock  = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$secondMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mappers = array(
    "FirstMapper" => $firstMapperMock,
    "SecondMapper" => $secondMapperMock
);

$firstColEntity1 = new Fixtures\Entity\Hybrid;
$firstColEntity1->id = 1;
$firstColEntity2 = new Fixtures\Entity\Hybrid;
$firstColEntity2->id = 2;
$firstColEntity3 = new Fixtures\Entity\Hybrid;
$firstColEntity3->id = 3;
$firstCol = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Hybrid");
$firstCol[] = $firstColEntity1;
$firstCol[] = $firstColEntity2;
$firstCol[] = $firstColEntity3;
$firstMapperMock->expects("findAll")->once()->andReturn($firstCol);

$secColEntity1 = new Fixtures\Entity\Hybrid;
$secColEntity1->id = 2;
$secCol = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Hybrid");
$secCol[] = $secColEntity1;
$secondMapperMock->expects("findAll")->once()->andReturn($secCol);



$query = new \UniMapper\Query\FindAll(new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Hybrid"), $mappers);
$result = $query->execute();

Assert::type("Unimapper\EntityCollection", $result);
Assert::same(1, count($result));
foreach ($result as $entity) {
    Assert::type("UniMapper\Tests\Fixtures\Entity\Hybrid", $entity);
    Assert::same(2, $entity->id);
    Assert::true($entity->isActive());
}