<?php

use Tester\Assert,
    UniMapper\Cache\ICache;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityFactoryTest extends UniMapper\Tests\TestCase
{

    public function testCreateEntity()
    {
        $factory = new \UniMapper\EntityFactory;

        // Autodetect entity
        $entity = $factory->createEntity(
            "Simple",
            ["text" => "foo", "publicProperty" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->publicProperty);

        // Force entity
        $nestedEntity = $factory->createEntity(
            "Nested",
            ["text" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedEntity);
        Assert::same("foo", $nestedEntity->text);
    }

    public function testCreateEntityFromCache()
    {
        $simpleRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Remote");

        $cacheMock = Mockery::mock("UniMapper\Tests\Fixtures\Cache\CustomCache");
        $cacheMock->shouldReceive("load")->with("UniMapper\Tests\Fixtures\Entity\Simple")->andReturn(false);
        $cacheMock->shouldReceive("save")->with(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Mockery::type("UniMapper\Reflection\Entity"),
            [
                ICache::FILES => [
                    $simpleRef->getFileName(),
                    $nestedRef->getFileName(),
                    $remoteRef->getFileName()
                ],
                ICache::TAGS => [ICache::TAG_REFLECTION]
            ]
        );

        $factory = new \UniMapper\EntityFactory($cacheMock);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $factory->createEntity("Simple"));
    }

    public function testCreateCollection()
    {
        $factory = new \UniMapper\EntityFactory;

        $collection = $factory->createCollection(
            "Simple",
            [["text" => "foo", "publicProperty" => "foo"]]
        );
        Assert::type("UniMapper\EntityCollection", $collection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $collection[0]);
        Assert::same("foo", $collection[0]->text);
        Assert::same("foo", $collection[0]->publicProperty);

        $nestedCollection = $factory->createCollection(
            "Nested",
            [["text" => "foo"]]
        );

        Assert::type("UniMapper\EntityCollection", $nestedCollection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedCollection[0]);
        Assert::same("foo", $nestedCollection[0]->text);
    }

}

$testCase = new EntityFactoryTest;
$testCase->run();