<?php

use Tester\Assert;
use UniMapper\NamingConvention as NC;

require __DIR__ . '/../bootstrap.php';

// trimNamespace()
Assert::same("Simple", NC::trimNamespace("UniMapper\Tests\Fixtures\Entity\Simple"));
Assert::same("Simple", NC::trimNamespace("Simple"));

// isValidMask()
Assert::same(true, NC::isValidMask("*"));
Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\*"));
Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\*Entity"));
Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\Entity*"));
Assert::same(false, NC::isValidMask("foo"));
Assert::same(false, NC::isValidMask("UniMapper\*\Fixtures\Entity\*"));
Assert::same(false, NC::isValidMask("UniMapper\*\Fixtures\Entity\**"));
Assert::same(false, NC::isValidMask("UniMapper\Tests\Fixtures\*\Entity"));
Assert::same(false, NC::isValidMask("UniMapper\Tests\Fixtures\Entity"));

// nameToClass()
Assert::same("UniMapper\Tests\Fixtures\Entity\Simple", NC::nameToClass("Simple", "UniMapper\Tests\Fixtures\Entity\*"));
Assert::exception(function() {
    NC::nameToClass("Simple", "foo");
}, "UniMapper\Exceptions\InvalidArgumentException", "Invalid mask 'foo'!");

// classToName()
Assert::same("Simple", NC::classToName("UniMapper\Tests\Fixtures\Entity\Simple", "UniMapper\Tests\Fixtures\Entity\*"));
Assert::exception(function() {
    NC::nameToClass("UniMapper\Tests\Fixtures\Entity\Simple", "foo");
}, "UniMapper\Exceptions\InvalidArgumentException", "Invalid mask 'foo'!");