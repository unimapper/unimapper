<?php

use Tester\Assert;
use UniMapper\NamingConvention as UNC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class NamingConventionTest extends \Tester\TestCase
{

    public function testSetMask()
    {
        UNC::setMask("*", UNC::ENTITY_MASK);
        UNC::setMask("UniMapper\Tests\Fixtures\Entity\*", UNC::ENTITY_MASK);
        UNC::setMask("UniMapper\Tests\Fixtures\Entity\*Entity", UNC::ENTITY_MASK);
        UNC::setMask("UniMapper\Tests\Fixtures\Entity\Entity*", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'foo'!
     */
    public function testSetMaskInvalidNoReplacementChar()
    {
        UNC::setMask("foo", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'UniMapper\*\Fixtures\Entity\*'!
     */
    public function testSetMaskInvalidMultipleReplacementChars()
    {
        UNC::setMask("UniMapper\*\Fixtures\Entity\*", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'UniMapper\Tests\Fixtures\*\Entity'!
     */
    public function testSetMaskInvalidWrongReplacementCharPosition()
    {
        UNC::setMask("UniMapper\Tests\Fixtures\*\Entity", UNC::ENTITY_MASK);
    }

    public function testNameToClass()
    {
        Assert::same("UniMapper\Tests\Fixtures\Entity\Simple", UNC::nameToClass("Simple", UNC::ENTITY_MASK));
        Assert::same("UniMapper\Tests\Fixtures\Repository\SimpleRepository", UNC::nameToClass("Simple", UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testNameToClassInvalidMask()
    {
        UNC::nameToClass("Simple", "foo");
    }

    public function testClassToName()
    {
        Assert::same("Simple", UNC::classToName("UniMapper\Tests\Fixtures\Entity\Simple", UNC::ENTITY_MASK));
        Assert::same("Simple", UNC::classToName("UniMapper\Tests\Fixtures\Repository\SimpleRepository", UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Class 'foo' not found!
     */
    public function testClassToNameClassNotFound()
    {
        UNC::classToName("foo", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testClassToNameInvalidMask()
    {
        UNC::classToName("UniMapper\Tests\Fixtures\Entity\Simple", "foo");
    }

    public function testGetMask()
    {
        Assert::same("UniMapper\Tests\Fixtures\Entity\*", UNC::getMask(UNC::ENTITY_MASK));
        Assert::same("UniMapper\Tests\Fixtures\Repository\*Repository", UNC::getMask(UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testGetMaskInvalidType()
    {
        UNC::getMask("foo");
    }

}

$testCase = new NamingConventionTest;
$testCase->run();