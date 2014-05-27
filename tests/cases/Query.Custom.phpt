<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getName")->once()->andReturn("FooMapper");
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_GET, null, null)->once()->andReturn([]);
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_PUT, "application/json", [])->once()->andReturn([]);
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_POST, null, [])->once()->andReturn([]);
$mapperMock->expects("custom")->with("resource", "query", \UniMapper\Query\Custom::METHOD_DELETE, null, null)->once();
$mapperMock->expects("custom")->with("resource", ["arg1", "arg2"], \UniMapper\Query\Custom::METHOD_RAW, null, null)->once();
$mapperMock->freeze();

// Get
$get = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$get->get("query");
Assert::same([], $get->execute());

// Put
$put = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$put->put("query", [], "application/json");
Assert::same([], $put->execute());

// Post
$post = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$post->post("query", []);
Assert::same([], $post->execute());

// Delete
$delete = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$delete->delete("query");
Assert::same(null, $delete->execute());

// Raw
$raw = new \UniMapper\Query\Custom(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock);
$raw->raw("arg1", "arg2");
Assert::same(null, $raw->execute());