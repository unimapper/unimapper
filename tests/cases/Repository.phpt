<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';


$repository = new Fixtures\Repository\SimpleRepository;
Assert::same("Simple", $repository->getName());
Assert::same("Simple", $repository->getEntityName());
Assert::exception(function() use ($repository) {
    $repository->query();
}, "UniMapper\Exceptions\RepositoryException", "You must set one mapper at least!");

$repository->registerMapper(new Fixtures\Mapper\Simple("FirstMapper"));
Assert::type("UniMapper\QueryBuilder", $repository->query());
Assert::type("UniMapper\QueryBuilder", $repository->query());

// createEntity()
Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $repository->createEntity("Simple"));
Assert::same("foo", $repository->createEntity("Simple", ["text" => "foo"])->text);