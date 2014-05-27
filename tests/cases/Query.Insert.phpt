<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getName")->once()->andReturn("FooMapper");
$mapperMock->expects("insert")->once()->andReturn("1");
$mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo"]);
$mapperMock->expects("mapValue")->once()->andReturn(1);
$mapperMock->freeze();

$query = new \UniMapper\Query\Insert(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, ["text" => "foo"]);
Assert::same(1, $query->execute());
Assert::same(['text' => 'foo'], $query->getValues());