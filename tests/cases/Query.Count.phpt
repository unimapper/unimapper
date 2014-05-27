<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getName")->once()->andReturn("FooMapper");
$mapperMock->expects("count")->with("resource", [["id", "=", 1, "AND"]])->once()->andReturn("1");
$mapperMock->freeze();
$query = new \UniMapper\Query\Count(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->where("id", "=", 1);
Assert::same(1, $query->execute());