<?php

use Tester\Assert;
use UniMapper\Convention;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ConventionTest extends TestCase
{

    public function testSetMask()
    {
        Convention::setMask("*", Convention::ENTITY_MASK);
        Convention::setMask("Tests\*", Convention::ENTITY_MASK);
        Convention::setMask("Tests\*Entity", Convention::ENTITY_MASK);
        Convention::setMask("Tests\Entity*", Convention::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'foo'!
     */
    public function testSetMaskInvalidNoReplacementChar()
    {
        Convention::setMask("foo", Convention::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'Tests\*\Entity\*'!
     */
    public function testSetMaskInvalidMultipleReplacementChars()
    {
        Convention::setMask("Tests\*\Entity\*", Convention::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask 'Tests\*\Entity'!
     */
    public function testSetMaskInvalidWrongReplacementCharPosition()
    {
        Convention::setMask("Tests\*\Entity", Convention::ENTITY_MASK);
    }

    public function testNameToClass()
    {
        Assert::same("Foo", Convention::nameToClass("Foo", Convention::ENTITY_MASK));
        Assert::same("FooRepository", Convention::nameToClass("Foo", Convention::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testNameToClassInvalidMask()
    {
        Convention::nameToClass("Foo", "foo");
    }

    public function testClassToName()
    {
        Assert::same("Foo", Convention::classToName("Foo", Convention::ENTITY_MASK));
        Assert::same("Foo", Convention::classToName("FooRepository", Convention::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Class 'undefined' not found!
     */
    public function testClassToNameClassNotFound()
    {
        Convention::classToName("undefined", Convention::ENTITY_MASK);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testClassToNameInvalidMask()
    {
        Convention::classToName("Foo", "foo");
    }

    public function testGetMask()
    {
        Assert::same("*", Convention::getMask(Convention::ENTITY_MASK));
        Assert::same("*Repository", Convention::getMask(Convention::REPOSITORY_MASK));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Invalid mask type foo!
     */
    public function testGetMaskInvalidType()
    {
        Convention::getMask("foo");
    }

}

class Foo extends \UniMapper\Entity {}
class FooRepository extends \UniMapper\Repository {}

$testCase = new ConventionTest;
$testCase->run();