<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$entity = new Entity;

$firstMapperMock = $mockista->create("TestMapper");
$secondMapperMock = $mockista->create("TestMapper");

$mappers = array();
$mappers["FirstMapper"] = $firstMapperMock;
$mappers["SecondMapper"] = $secondMapperMock;

$builder = new \UniMapper\QueryBuilder($entity->getReflection(), $mappers);

// Built-in queries
Assert::type("UniMapper\Query\Count", $builder->count());
Assert::type("UniMapper\Query\FindAll", $builder->findAll());
Assert::type("UniMapper\Query\FindOne", $builder->findOne(1));
Assert::type("UniMapper\Query\Update", $builder->update(array()));
Assert::type("UniMapper\Query\Insert", $builder->insert($entity));
Assert::type("UniMapper\Query\Custom", $builder->custom("FirstMapper"));
Assert::type("UniMapper\Query\Delete", $builder->delete());

// Custom query
$builder->registerQuery("TestQuery");
Assert::type("TestQuery", $builder->testQuery());