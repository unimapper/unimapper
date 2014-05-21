<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->text = "foo";

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("insert")->once()->andReturn("1");
$mapperMock->expects("getResource")->once()->andReturn("resource");
$mapperMock->expects("unmapEntity")->with($entity)->once()->andReturn(["text" => "foo"]);
$mapperMock->expects("mapValue")->once()->andReturn(1);

$query = new \UniMapper\Query\Insert(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, $entity);
Assert::same(1, $query->execute());