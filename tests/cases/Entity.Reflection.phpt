<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionTest extends \Tester\TestCase
{

    public function testCreateEntity()
    {
        $reflection = new Reflection(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );

        $entity = $reflection->createEntity(
            ["text" => "foo", "readonly" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->readonly);
    }

    public function testNoAdapterDefined()
    {
        $reflection = new Reflection("UniMapper\Tests\Fixtures\Entity\NoAdapter");
        Assert::same("UniMapper\Tests\Fixtures\Entity\NoAdapter", $reflection->getClassName());
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Property 'id' already defined as public property!
     */
    public function testDuplicatePublicProperty()
    {
        new Reflection("UniMapper\Tests\Fixtures\Entity\DuplicatePublicProperty");
    }

    public function testNoPropertyDefined()
    {
        $reflection = new Reflection("UniMapper\Tests\Fixtures\Entity\NoProperty");
        Assert::count(0, $reflection->getProperties());
    }

    public function testGetProperties()
    {
        $reflection = new Reflection("UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::same(
            array(
                'id',
                'text',
                'empty',
                'url',
                'email',
                'time',
                'date',
                'year',
                'ip',
                'mark',
                'entity',
                'collection',
                'oneToMany',
                'oneToManyRemote',
                'manyToMany',
                'mmFilter',
                'manyToOne',
                'oneToOne',
                'ooFilter',
                'readonly',
                'storedData',
                'enumeration',
                'disabledMap'
            ),
            array_keys($reflection->getProperties())
        );
    }

    public function testHasPrimary()
    {
        $noPrimary = new Reflection(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        Assert::false($noPrimary->hasPrimary());
        $simple = new Reflection(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        Assert::true($simple->hasPrimary());
    }

    public function testGetPrimaryProperty()
    {
        $reflection = new Reflection(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        Assert::same("id", $reflection->getPrimaryProperty()->getName());
    }

    /**
     * @throws Exception Primary property not defined in UniMapper\Tests\Fixtures\Entity\NoPrimary!
     */
    public function testGetPrimaryPropertyNotDefined()
    {
        $reflection = new Reflection(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        $reflection->getPrimaryProperty();
    }

    public function testLoadWithClass()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load("UniMapper\Tests\Fixtures\Entity\Simple")->getClassName()
        );
    }

    public function testLoadWithName()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load("Simple")->getClassName()
        );
    }

    public function testLoadWithEntity()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load(new \UniMapper\Tests\Fixtures\Entity\Simple)->getClassName()
        );
    }

    public function testLoadWithCollection()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load(\UniMapper\Tests\Fixtures\Entity\Simple::createCollection())->getClassName()
        );
    }

    public function testLoadWithReflection()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load(new Reflection("UniMapper\Tests\Fixtures\Entity\Simple"))->getClassName()
        );
    }

    /**
     * @throws \UniMapper\Exception\InvalidArgumentException Entity identifier must be object, collection, class or name!
     */
    public function testLoadInvalidArgument()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load(null)->getClassName()
        );
    }

    /**
     * @throws \UniMapper\Exception\InvalidArgumentException Entity class UniMapper\Tests\Fixtures\Entity\Undefined not found!
     */
    public function testLoadUndefinedClass()
    {
        Assert::same(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Reflection::load("Undefined")->getClassName()
        );
    }

}

$testCase = new EntityReflectionTest;
$testCase->run();