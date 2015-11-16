<?php

use UniMapper\Entity;
use UniMapper\Convention;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionPropertyOptionAssocTest extends TestCase
{

    public function testCreateManyToMany()
    {
        $association = Entity\Reflection::load("Foo")->getProperty("manyToMany")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::type("UniMapper\Association\ManyToMany", $association);
        Assert::false($association->isRemote());
        Assert::same("sourceKey", $association->getJoinKey());
        Assert::same("source_target", $association->getJoinResource());
        Assert::same("targetKey", $association->getReferencingKey());
    }

    public function testCreateManyToManyRemote()
    {
        $association = Entity\Reflection::load("Bar")->getProperty("manyToMany")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::true($association->isRemote());
        Assert::true($association->isDominant());
        Assert::same("sourceKey", $association->getJoinKey());
        Assert::same("source_target", $association->getJoinResource());
        Assert::same("targetKey", $association->getReferencingKey());
    }

    public function testCreateManyToManyRemoteNotDominant()
    {
        $association = Entity\Reflection::load("Foo")->getProperty("notDominant")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::true($association->isRemote());
        Assert::false($association->isDominant());
        Assert::same("sourceKey", $association->getJoinKey());
        Assert::same("source_target", $association->getJoinResource());
        Assert::same("targetKey", $association->getReferencingKey());
    }

    public function testCreateOneToMany()
    {
        $association = Entity\Reflection::load("Foo")->getProperty("oneToMany")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::type("UniMapper\Association\OneToMany", $association);
        Assert::same("sourceKey", $association->getReferencedKey());
    }

    public function testCreateOneToOne()
    {
        $association = Entity\Reflection::load("Foo")->getProperty("oneToOne")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::type("UniMapper\Association\OneToOne", $association);
        Assert::same("targetKey", $association->getReferencingKey());
    }

    public function testCreateManyToOne()
    {
        $association = Entity\Reflection::load("Foo")->getProperty("manyToOne")->getOption(Entity\Reflection\Property\Option\Assoc::KEY);
        Assert::type("UniMapper\Association\ManyToOne", $association);
        Assert::same("targetKey", $association->getReferencingKey());
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testCreateMapNotAllowed()
    {
        Entity\Reflection::load("Map");
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testCreateEnumNotAllowed()
    {
        Entity\Reflection::load("Enum");
    }

    /**
     * @throws UniMapper\Exception\ReflectionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testCreateComputedNotAllowed()
    {
        Entity\Reflection::load("Computed");
    }

}

/**
 * @adapter FooAdapter()
 *
 * @property int   $id          m:primary
 * @property Foo[] $manyToMany  m:assoc(M:N) m:assoc-by(sourceKey|source_target|targetKey)
 * @property Bar[] $notDominant m:assoc(M<N) m:assoc-by(sourceKey|source_target|targetKey)
 * @property Foo[] $oneToMany   m:assoc(1:N) m:assoc-by(sourceKey)
 * @property Foo   $oneToOne    m:assoc(1:1) m:assoc-by(targetKey)
 * @property Foo   $manyToOne   m:assoc(N:1) m:assoc-by(targetKey)
 */
class Foo extends Entity {}

/**
 * @adapter BarAdapter()
 *
 * @property int   $id         m:primary
 * @property Foo[] $manyToMany m:assoc(M:N) m:assoc-by(sourceKey|source_target|targetKey)
 */
class Bar extends Entity {}

/**
 * @adapter Foo()
 *
 * @property int $id  m:primary
 * @property Map $foo m:assoc(1:1) m:assoc-by(targetKey) m:map-by(foo)
 */
class Map extends Entity {}

/**
 * @adapter Foo()
 *
 * @property int $id  m:primary
 * @property Map $foo m:assoc(1:1) m:assoc-by(targetKey) m:enum(self::ENUM_*)
 */
class Enum extends Entity {}

/**
 * @adapter Foo()
 *
 * @property int  $id  m:primary
 * @property Map $foo m:assoc(1:1) m:assoc-by(targetKey) m:computed
 */
class Computed extends Entity
{
    public function computeFoo() {}
}

$testCase = new EntityReflectionPropertyOptionAssocTest;
$testCase->run();