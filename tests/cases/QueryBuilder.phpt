<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->text = "foo";

$builder = new \UniMapper\QueryBuilder(
    $entity->getReflection(),
    $mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple")
);
$mapperMock->expects("unmapEntity")->twice()->andReturn(["text" => "foo"]);

// Built-in queries
Assert::type("UniMapper\Query\Count", $builder->count());
Assert::type("UniMapper\Query\FindAll", $builder->findAll());
Assert::type("UniMapper\Query\FindOne", $builder->findOne(1));
Assert::type("UniMapper\Query\Update", $builder->update($entity));
Assert::type("UniMapper\Query\Insert", $builder->insert($entity));
Assert::type("UniMapper\Query\Custom", $builder->custom());
Assert::type("UniMapper\Query\Delete", $builder->delete());

// Custom query
$builder->registerQuery("UniMapper\Tests\Fixtures\Query\Simple");
Assert::type("UniMapper\Tests\Fixtures\Query\Simple", $builder->simple());