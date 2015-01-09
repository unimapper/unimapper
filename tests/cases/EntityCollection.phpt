<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityCollectionTest extends UniMapper\Tests\TestCase
{

    public function testCreateCollection()
    {
        $entity = $this->createEntity("Simple", ["text" => "test"]);

        $collection = new UniMapper\EntityCollection("Simple");

        $collection[] = $entity;
        Assert::same("test", $collection[0]->text);

        $entity->text = "foo";
        $collection[] = $entity;

        foreach ($collection as $entity) {
            Assert::type(get_class($entity), $entity);
            Assert::same("foo", $entity->text);
        }
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values must be traversable data!
     */
    public function testValuesNotTraversable()
    {
        new UniMapper\EntityCollection("Simple", "foo");
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected instance of entity UniMapper\Tests\Fixtures\Entity\Simple!
     */
    public function testInvalidEntity()
    {
        new UniMapper\EntityCollection("Simple", [new UniMapper\Tests\Fixtures\Entity\Remote]);
    }

}

$testCase = new EntityCollectionTest;
$testCase->run();