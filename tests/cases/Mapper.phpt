<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class MapperTest extends Tester\TestCase
{

    /** @var \UniMapper\Tests\Fixtures\Mapper\Simple */
    private $mapper;

    public function setUp()
    {
        $this->mapper = new Fixtures\Mapper\Simple("FooMapper");
    }

    public function testGetName()
    {
        Assert::same("FooMapper", $this->mapper->getName());
    }

    public function testMapEntity()
    {
        $entity = $this->mapper->mapEntity(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            [
                "email_address" => "john.doe@example.com",
                "publicProperty" => "foo",
                "undefined" => 1,
                "link" => "http://example.com",
                "readonly" => "foo"
            ]
        );

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("john.doe@example.com", $entity->email);
        Assert::same("defaultValue", $entity->publicProperty);
        Assert::same("http://example.com", $entity->url);
        Assert::same("foo", $entity->readonly);
    }

    public function testUnmapEntity()
    {
        $entity = new Fixtures\Entity\Simple;
        $entity->email = "john.doe@example.com";
        $entity->url = "http://example.com";
        $entity->empty = null;

        Assert::same(
            [
                "email_address" => $entity->email,
                "link" => $entity->url,
                "empty" => null
            ],
            $this->mapper->unmapEntity($entity)
        );
    }

    public function testMapCollection()
    {
        $collection = $this->mapper->mapCollection(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            [
                [
                    "email_address" => "john.doe@example.com",
                    "publicProperty" => "foo",
                    "undefined" => 1,
                    "readonly" => "foo"
                ]
            ]
        );
        Assert::type("UniMapper\EntityCollection", $collection);
        Assert::count(1, $collection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $collection[0]);
        Assert::same("john.doe@example.com", $collection[0]->email);
        Assert::same("defaultValue", $collection[0]->publicProperty);
        Assert::same("foo", $collection[0]->readonly);
    }

    public function testUnmapCollection()
    {
        $entity = new Fixtures\Entity\Simple;
        $entity->email = "john.doe@example.com";
        $entity->url = "http://example.com";
        $entity->publicProperty = "foo";

        $collection = new \UniMapper\EntityCollection(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        $collection[] = $entity;

        Assert::same(
            [['email_address' => $entity->email, 'link' => $entity->url]],
            $this->mapper->unmapCollection($collection)
        );
    }

}

$testCase = new MapperTest;
$testCase->run();