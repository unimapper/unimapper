<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");

Assert::exception(function() use ($mapperMock) {
    new \UniMapper\Query\Update(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, ["id" => 1]);
}, "UniMapper\Exceptions\QueryException", "Update is not allowed on primary property 'id'!");

Assert::exception(function() use ($mapperMock) {
    $mapperMock->expects("unmapEntity")->once()->andReturn([]);
    new \UniMapper\Query\Update(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, []);
}, "UniMapper\Exceptions\QueryException", "Nothing to update!");

$mapperMock->expects("update")->once()->andReturn("1");
$mapperMock->expects("getResource")->once()->andReturn("resource");
$mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo"]);
$mapperMock->expects("unmapConditions")->once()->andReturn(["id", "=", 1]);

$query = new \UniMapper\Query\Update(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, ["text" => "foo"]);
$query->where("id", "=", 1);
Assert::same(null, $query->execute());