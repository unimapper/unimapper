<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionTest extends TestCase
{

    public function testCreateEntity()
    {
        $reflection = new Reflection("Entity");
        $entity = $reflection->createEntity(["foo" => "bar", "readonly" => 1]);
        Assert::type("Entity", $entity);
        Assert::same("bar", $entity->foo);
        Assert::same(1, $entity->readonly);
    }

    public function testGetAdapterName()
    {
        $reflection = new Reflection("Adapter");
        Assert::same("Foo", $reflection->getAdapterName());
    }

    public function testGetAdapterResource()
    {
        $reflection = new Reflection("Adapter");
        Assert::same("bar", $reflection->getAdapterResource());
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Property 'foo' already defined as public property!
     */
    public function testDuplicatePublicProperty()
    {
        new Reflection("DuplicatePublicProperty");
    }

    public function testGetPropertiesEmpty()
    {
        $reflection = new Reflection("Adapter");
        Assert::count(0, $reflection->getProperties());
    }

    public function testGetProperties()
    {
        $reflection = new Reflection("Entity");
        Assert::same(
            array(
                'foo',
                'readonly'
            ),
            array_keys($reflection->getProperties())
        );
    }

    public function testHasPrimary()
    {
        $entity = new Reflection("Entity");
        Assert::false($entity->hasPrimary());
        $primary = new Reflection("Primary");
        Assert::true($primary->hasPrimary());
    }

    public function testGetPrimaryProperty()
    {
        $primary = new Reflection("Primary");
        Assert::type(
            "UniMapper\Entity\Reflection\Property",
            $primary->getPrimaryProperty()
        );
        Assert::same("id", $primary->getPrimaryProperty()->getName());
    }

    /**
     * @throws Exception Primary property not defined in Entity!
     */
    public function testGetPrimaryPropertyUndefined()
    {
        $reflection = new Reflection("Entity");
        $reflection->getPrimaryProperty();
    }

    public function testLoadWithClass()
    {
        Assert::same("Entity", Reflection::load("Entity")->getClassName());
    }

    public function testLoadWithName()
    {
        Assert::same(
            "Entity",
            Reflection::load("Entity")->getName()
        );
    }

    public function testLoadWithEntity()
    {
        Assert::same("Entity", Reflection::load(new Entity())->getClassName());
    }

    public function testLoadWithCollection()
    {
        Assert::same(
            "Entity",
            Reflection::load(Entity::createCollection())->getClassName()
        );
    }

    public function testLoadWithReflection()
    {
        Assert::same(
            "Entity",
            Reflection::load(new Reflection("Entity"))->getClassName()
        );
    }

    /**
     * @throws \UniMapper\Exception\InvalidArgumentException Entity identifier must be object, collection, class or name!
     */
    public function testLoadInvalidArgument()
    {
        Reflection::load(null)->getClassName();
    }

    /**
     * @throws \UniMapper\Exception\InvalidArgumentException Entity class Undefined not found!
     */
    public function testLoadUndefinedClass()
    {
        Reflection::load("Undefined")->getClassName();
    }

}

/**
 * @property string $foo
 *
 * @property-read int $readonly
 */
class Entity extends \UniMapper\Entity {}

/** @adapter Foo(bar) */
class Adapter extends \UniMapper\Entity {}

/** @property string $foo */
class DuplicatePublicProperty extends \UniMapper\Entity
{
    public $foo;
}

/** @property int $id m:primary */
class Primary extends \UniMapper\Entity
{}

$testCase = new EntityReflectionTest;
$testCase->run();