<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");

Assert::exception(function() use ($mapperMock) {
    $query = new \UniMapper\Query\Delete(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
    $query->execute();
}, "UniMapper\Exceptions\QueryException", "At least one condition must be set!");

$mapperMock->expects("delete")->with("resource", ["id", "=", 1])->once();
$mapperMock->expects("getResource")->once()->andReturn("resource");
$mapperMock->expects("unmapConditions")->once()->andReturn(["id", "=", 1]);
$query = new \UniMapper\Query\Delete(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->where("id", "=", 1);
$query->execute();