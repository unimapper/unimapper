<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class EntityCollectionTest extends UniMapper\Tests\TestCase
{

    public function testCreateCollection()
    {
        $entity = $this->createEntity("Simple", ["text" => "test"]);

        $collection = new UniMapper\EntityCollection($entity->getReflection());

        $collection[] = $entity;
        Assert::same("test", $collection[0]->text);

        $entity->text = "foo";
        $collection[] = $entity;

        foreach ($collection as $entity) {
            Assert::type(get_class($entity), $entity);
            Assert::same("foo", $entity->text);
        }
    }

}

$testCase = new EntityCollectionTest;
$testCase->run();