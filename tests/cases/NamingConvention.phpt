<?php

use Tester\Assert,
    UniMapper\NamingConvention as NC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class NamingConventionTest extends UniMapper\Tests\TestCase
{

    public function testTrimNamespace()
    {
        Assert::same("Simple", NC::trimNamespace("UniMapper\Tests\Fixtures\Entity\Simple"));
        Assert::same("Simple", NC::trimNamespace("Simple"));
    }

    public function testIsValidMask()
    {
        Assert::same(true, NC::isValidMask("*"));
        Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\*"));
        Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\*Entity"));
        Assert::same(true, NC::isValidMask("UniMapper\Tests\Fixtures\Entity\Entity*"));
        Assert::same(false, NC::isValidMask("foo"));
        Assert::same(false, NC::isValidMask("UniMapper\*\Fixtures\Entity\*"));
        Assert::same(false, NC::isValidMask("UniMapper\*\Fixtures\Entity\**"));
        Assert::same(false, NC::isValidMask("UniMapper\Tests\Fixtures\*\Entity"));
        Assert::same(false, NC::isValidMask("UniMapper\Tests\Fixtures\Entity"));
    }

    public function testNameToClass()
    {
        Assert::same("UniMapper\Tests\Fixtures\Entity\Simple", NC::nameToClass("Simple", NC::$entityMask));
        Assert::same("UniMapper\Tests\Fixtures\Repository\SimpleRepository", NC::nameToClass("Simple", NC::$repositoryMask));
        Assert::exception(function() {
            NC::nameToClass("Simple", "foo");
        }, "UniMapper\Exception\InvalidArgumentException", "Invalid mask 'foo'!");
    }

    public function testClassToName()
    {
        Assert::same("Simple", NC::classToName("UniMapper\Tests\Fixtures\Entity\Simple", NC::$entityMask));
        Assert::same("Simple", NC::classToName("UniMapper\Tests\Fixtures\Repository\SimpleRepository", NC::$repositoryMask));
        Assert::exception(function() {
            NC::classToName("UniMapper\Tests\Fixtures\Entity\Simple", "foo");
        }, "UniMapper\Exception\InvalidArgumentException", "Invalid mask 'foo'!");
        Assert::exception(function() {
            NC::classToName("foo", NC::$entityMask);
        }, "UniMapper\Exception\InvalidArgumentException", "Class 'foo' not found!");
    }

}

$testCase = new NamingConventionTest;
$testCase->run();