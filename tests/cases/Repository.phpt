<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

// Set default entity
$repository = new Fixtures\Repository\SimpleRepository("UniMapper\Tests\Fixtures\Entity\Simple");
Assert::same("UniMapper\Tests\Fixtures\Entity\Simple", $repository->getEntityClass());

// Change default entity
$repository->setEntityClass("UniMapper\Tests\Fixtures\Entity\NoMapper");
Assert::same("UniMapper\Tests\Fixtures\Entity\NoMapper", $repository->getEntityClass());

// Can not detect default entity class automatically
Assert::exception(function() {
    new Fixtures\Repository\BadConvention;
}, "UniMapper\Exceptions\RepositoryException", "You must set default entity class in repository UniMapper\Tests\Fixtures\Repository\BadConvention!");

// Undefined entity set
Assert::exception(function() use ($repository) {
    $repository->setEntityClass("foo");
}, "UniMapper\Exceptions\RepositoryException", "Can not set class 'foo' as default entity in repository UniMapper\Tests\Fixtures\Repository\SimpleRepository!");