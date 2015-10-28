<?php

use UniMapper\Entity;
use UniMapper\NamingConvention as UNC;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

UNC::setMask("*", UNC::ENTITY_MASK);

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
 * @property int     $id  m:primary
 * @property Mapping $foo m:assoc(1:1) m:assoc-by(targetKey) m:map-by(foo)
 */
class Mapping extends Entity {}

/**
 * @testCase
 */
class EntityReflectionPropertyOptionAssocTest extends \Tester\TestCase
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

    public function testCreateMappingNotAllowed()
    {
        Entity\Reflection::load("Mapping");
    }

}

$testCase = new EntityReflectionPropertyOptionAssocTest;
$testCase->run();