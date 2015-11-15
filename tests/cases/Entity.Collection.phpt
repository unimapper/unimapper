<?php

use Tester\Assert;
use UniMapper\Entity\Collection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityCollectionTest extends TestCase
{

    public function testConstruct()
    {
        $collection = new Collection("Foo", [["id" => 1]]);
        Assert::same(1, count($collection));
        Assert::same(1, $collection[0]->id);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values must be traversable data!
     */
    public function testConstructValuesNotTraversable()
    {
        new Collection("Foo", "foo");
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected instance of entity Foo!
     */
    public function testConstructInvalidEntity()
    {
        new Collection("Foo", [new Bar]);
    }

    public function testAdd()
    {
        $entity = new Foo(["id" => 1]);

        $collection = new Collection("Foo");
        $collection->add($entity);

        Assert::same([$entity], $collection->getChanges()[Foo::CHANGE_ADD]);
    }

    public function testAttach()
    {
        $entity = new Foo(["id" => 1]);

        $collection = new Collection("Foo");
        $collection->attach($entity);

        Assert::same([1], $collection->getChanges()[Foo::CHANGE_ATTACH]);
    }

    public function testDetach()
    {
        $entity = new Foo(["id" => 1]);

        $collection = new Collection("Foo");
        $collection->detach($entity);

        Assert::same([1], $collection->getChanges()[Foo::CHANGE_DETACH]);
    }

    public function testRemove()
    {
        $entity = new Foo(["id" => 1]);

        $collection = new Collection("Foo");
        $collection->remove($entity);

        Assert::same([1], $collection->getChanges()[Foo::CHANGE_REMOVE]);
    }

    public function testJsonSerialize()
    {
        $collection = new Collection("Foo");
        $collection[] = new Foo(["id" => 1]);
        Assert::same('[{"id":1,"entity":null,"collection":[]}]', json_encode($collection));
    }

    public function testGetIterator()
    {
        $foo = new Foo(["id" => 1]);
        foreach (new Collection("Foo", [$foo]) as $index => $entity) {

            Assert::same(0, $index);
            Assert::same($foo, $entity);
        }
    }

    public function testGetByPrimary()
    {
        $entity = new Foo(["id" => 1]);
        $collection = new Collection("Foo", [$entity]);
        Assert::same($entity, $collection->getByPrimary(1));
    }

    public function testToArray()
    {
        $barEntity = new Bar;
        $barCollection = new Collection("Bar");
        $collection = new Collection("Foo", [["collection" => $barCollection, "entity" => $barEntity]]);

        Assert::same(
            [
                [
                    "id" => null,
                    "entity" => $barEntity,
                    "collection" => $barCollection
                ]
            ],
            $collection->toArray()
        );
        Assert::same(
            [
                [
                    "id" => null,
                    "entity" => [],
                    "collection" => []
                ]
            ],
            $collection->toArray(true)
        );
    }

}

/**
 * @property int   $id         m:primary
 * @property Bar   $entity
 * @property Bar[] $collection
 */
class Foo extends \UniMapper\Entity {}

class Bar extends \UniMapper\Entity {}

$testCase = new EntityCollectionTest;
$testCase->run();