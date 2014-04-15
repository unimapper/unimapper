<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$firstMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$firstMapperMock->expects("insert")->once()->andReturn(1);

$secondMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$secondMapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $firstMapperMock;
$mappers["SecondMapper"] = $secondMapperMock;

$query = new \UniMapper\Query\Insert(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Hybrid"), $mappers, ["first" => "foo"]);

Assert::true($query->returnPrimaryValue);

$result = $query->execute();

Assert::type("UniMapper\Tests\Fixtures\Entity\Hybrid", $result);
Assert::same(1, $result->id);
Assert::true($result->isActive());
Assert::false($query->returnPrimaryValue);