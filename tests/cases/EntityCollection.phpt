<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class EntityCollectionTest extends Tester\TestCase
{

    public function testCreateCollection()
    {
        $entity = new Fixtures\Entity\Simple;
        $entity->text = "test";

        $collection = new UniMapper\EntityCollection(get_class($entity));

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