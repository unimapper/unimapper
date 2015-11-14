<?php

use UniMapper\Entity;
use UniMapper\NamingConvention as UNC;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

UNC::setMask("*", UNC::ENTITY_MASK);

/** @property int $id m:enum(self::ENUMERATION_) */
class InvalidDefinition extends Entity {}

/** @property int $id m:enum(Undefined::TYPE_*) */
class ClassNotFound extends Entity {}

/**
 * @property int $self  m:enum(self::TYPE_*)
 * @property int $class m:enum(Enum::TYPE_*)
 */
class Enum extends Entity
{
    const TYPE_ONE = 1;
    const TYPE_TWO = 2;
}

/**
 * @testCase
 */
class EntityReflectionPropertyOptionEnumTest extends \Tester\TestCase
{

    public function testCreateWithSelfClass()
    {
        Assert::same(
            ['TYPE_ONE' => 1, 'TYPE_TWO' => 2],
            Entity\Reflection::load("Enum")
                ->getProperty("self")
                ->getOption(Entity\Reflection\Property\Option\Enum::KEY)
                ->getValues()
        );
    }

    public function testCreateWithClassSpecified()
    {
        Assert::same(
            ['TYPE_ONE' => 1, 'TYPE_TWO' => 2],
            Entity\Reflection::load("Enum")
                ->getProperty("class"
                )->getOption(Entity\Reflection\Property\Option\Enum::KEY)
                ->getValues()
        );
    }

    public function testIsValid()
    {
        $enum = Entity\Reflection::load("Enum")
            ->getProperty("self")
            ->getOption(Entity\Reflection\Property\Option\Enum::KEY);

        Assert::true($enum->isValid(1));
        Assert::false($enum->isValid(3));
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Invalid enumeration definition!
     */
    public function testCreateInvalidDefinition()
    {
        Entity\Reflection::load("InvalidDefinition");
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Enumeration class Undefined not found!
     */
    public function testCreateClassNotFound()
    {
        Entity\Reflection::load("ClassNotFound");
    }

}

$testCase = new EntityReflectionPropertyOptionEnumTest;
$testCase->run();