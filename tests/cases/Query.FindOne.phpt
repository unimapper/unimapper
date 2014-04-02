<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->id = 1;

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mappers = array("FirstMapper" =>  $mapperMock);



$mapperMock->expects("findOne")->once()->andReturn($entity);
$query = new \UniMapper\Query\FindOne(new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $mappers, $entity->id);
$result = $query->execute();

Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
Assert::true($result->isActive());