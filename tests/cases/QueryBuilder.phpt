<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("getName")->once()->andReturn("FooMapper");
$mapperMock->expects("unmapEntity")->twice()->andReturn(["text" => "foo"]);
$mapperMock->freeze();

$builder = new \UniMapper\QueryBuilder(
    new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
    ["FooMapper" => $mapperMock]
);

// Built-in queries
Assert::type("UniMapper\Query\Count", $builder->count());
Assert::type("UniMapper\Query\FindAll", $builder->findAll());
Assert::type("UniMapper\Query\FindOne", $builder->findOne(1));
Assert::type("UniMapper\Query\UpdateOne", $builder->updateOne(1, ["text" => "foo"]));
Assert::type("UniMapper\Query\Update", $builder->update(["text" => "foo"]));
Assert::type("UniMapper\Query\Insert", $builder->insert(["text" => "foo"]));
Assert::type("UniMapper\Query\Custom", $builder->custom());
Assert::type("UniMapper\Query\Delete", $builder->delete());

// Custom query
$builder->registerQuery("UniMapper\Tests\Fixtures\Query\Simple");
Assert::type("UniMapper\Tests\Fixtures\Query\Simple", $builder->simple());