<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

// Simple entity
$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("insert")->once()->andReturn(1);


$query = new \UniMapper\Query\Insert(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mapperMock, ["text" => "foo"]);

$result = $query->execute();

Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
Assert::same(1, $result->id);
Assert::true($result->isActive());