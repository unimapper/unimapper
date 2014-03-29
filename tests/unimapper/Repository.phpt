<?php

use Tester\Assert,
    UniMapper\Tests;

require __DIR__ . '/../bootstrap.php';

// Set default entity
$repository = new Tests\EntityRepository;
Assert::same("Entity", $repository->detectEntityClass());

// Change default entity
$repository->setEntityClass("NoMapperEntity");
Assert::same("NoMapperEntity", $repository->getEntityClass());

// Can not detect default entity class automatically
Assert::exception(function() use ($repository) {
    new Tests\BadConvention;
}, "UniMapper\Exceptions\RepositoryException", "You must set default entity class in repository UniMapper\Tests\BadConvention!");

// Undefined entity set
Assert::exception(function() use ($repository) {
    $repository->setEntityClass("foo");
}, "UniMapper\Exceptions\RepositoryException", "Can not set class 'foo' as default entity in repository UniMapper\Tests\EntityRepository!");