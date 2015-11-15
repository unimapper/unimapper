<?php

use Tester\Assert;
use UniMapper\NamingConvention as UNC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class NamingConventionTest extends TestCase
{

    public function testSetMask()
    {
        UNC::setMask("*", UNC::ENTITY_MASK);
        UNC::setMask("Tests\*", UNC::ENTITY_MASK);
        UNC::setMask("Tests\*Entity", UNC::ENTITY_MASK);
        UNC::setMask("Tests\Entity*", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'foo'!
     */
    public function testSetMaskInvalidNoReplacementChar()
    {
        UNC::setMask("foo", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'Tests\*\Entity\*'!
     */
    public function testSetMaskInvalidMultipleReplacementChars()
    {
        UNC::setMask("Tests\*\Entity\*", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'Tests\*\Entity'!
     */
    public function testSetMaskInvalidWrongReplacementCharPosition()
    {
        UNC::setMask("Tests\*\Entity", UNC::ENTITY_MASK);
    }

    public function testNameToClass()
    {
        Assert::same("Foo", UNC::nameToClass("Foo", UNC::ENTITY_MASK));
        Assert::same("FooRepository", UNC::nameToClass("Foo", UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testNameToClassInvalidMask()
    {
        UNC::nameToClass("Foo", "foo");
    }

    public function testClassToName()
    {
        Assert::same("Foo", UNC::classToName("Foo", UNC::ENTITY_MASK));
        Assert::same("Foo", UNC::classToName("FooRepository", UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Class 'undefined' not found!
     */
    public function testClassToNameClassNotFound()
    {
        UNC::classToName("undefined", UNC::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testClassToNameInvalidMask()
    {
        UNC::classToName("Foo", "foo");
    }

    public function testGetMask()
    {
        Assert::same("*", UNC::getMask(UNC::ENTITY_MASK));
        Assert::same("*Repository", UNC::getMask(UNC::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testGetMaskInvalidType()
    {
        UNC::getMask("foo");
    }

}

class Foo extends \UniMapper\Entity {}
class FooRepository extends \UniMapper\Repository {}

$testCase = new NamingConventionTest;
$testCase->run();