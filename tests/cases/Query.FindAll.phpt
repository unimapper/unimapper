<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity1 = new Fixtures\Entity\Simple;
$entity1->id = 2;
$entity2 = new Fixtures\Entity\Simple;
$entity2->id = 3;

$collection = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple");
$collection[] = $entity1;
$collection[] = $entity2;

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getResource")->once()->andReturn("resource");
$mapperMock->expects("unmapSelection")->once()->andReturn(["link", "text"]);
$mapperMock->expects("unmapConditions")->once()->andReturn(["id", ">", 1]);
$mapperMock->expects("unmapOrderBy")->once()->andReturn(["id" => "DESC"]);
$mapperMock->expects("findAll")
    ->with("resource", ["link", "text"], ["id", ">", 1], ["id" => "DESC"], 0, 0)
    ->once()
    ->andReturn([["id" => 2], ["id" => 3]]);
$mapperMock->expects("mapCollection")->with(get_class($entity1), [["id" => 2], ["id" => 3]])->once()->andReturn($collection);

$query = new \UniMapper\Query\FindAll(new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, "url", "text");
$query->where("id", ">", 1)->orderBy("id", "DESC");
$result = $query->execute();

Assert::type("Unimapper\EntityCollection", $result);
Assert::same(2, count($result));
foreach ($result as $entity) {
    Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
    Assert::true($entity->isActive());
}