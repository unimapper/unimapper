<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getResource")->once()->andReturn("resource");

// Get
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_GET, null, null)->once()->andReturn([]);
$query = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->get("query");
Assert::same([], $query->execute());

// Put
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_PUT, "application/json", [])->once()->andReturn([]);
$query = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->put("query", [], "application/json");
Assert::same([], $query->execute());

// Post
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_POST, null, [])->once()->andReturn([]);
$query = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->post("query", []);
Assert::same([], $query->execute());

// Delete
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_DELETE, null, null)->once();
$query = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->delete("query");
Assert::same(null, $query->execute());

// Raw
$mapperMock->expects("custom")->with("resource", ["arg1", "arg2"], \UniMapper\Query\Custom::METHOD_RAW, null, null)->once();
$query = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$query->raw("arg1", "arg2");
Assert::same(null, $query->execute());