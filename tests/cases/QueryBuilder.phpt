<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;

$firstMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$secondMapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");

$mappers = array();
$mappers["FirstMapper"] = $firstMapperMock;
$mappers["SecondMapper"] = $secondMapperMock;

$builder = new \UniMapper\QueryBuilder($entity->getReflection(), $mappers);

// Built-in queries
Assert::type("UniMapper\Query\Count", $builder->count());
Assert::type("UniMapper\Query\FindAll", $builder->findAll());
Assert::type("UniMapper\Query\FindOne", $builder->findOne(1));
Assert::type("UniMapper\Query\Update", $builder->update(array()));
Assert::type("UniMapper\Query\Insert", $builder->insert([]));
Assert::type("UniMapper\Query\Custom", $builder->custom("FirstMapper"));
Assert::type("UniMapper\Query\Delete", $builder->delete());

// Custom query
$builder->registerQuery("UniMapper\Tests\Fixtures\Query\Simple");
Assert::type("UniMapper\Tests\Fixtures\Query\Simple", $builder->simple());