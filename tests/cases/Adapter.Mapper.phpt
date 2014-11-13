<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class AdapterMapperTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Adapter\Mapper */
    private $mapper;

    public function setUp()
    {
        $this->mapper = new UniMapper\Adapter\Mapper;
    }

    public function testMapEntity()
    {
        $entity = $this->mapper->mapEntity(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            [
                "email_address" => "john.doe@example.com",
                "publicProperty" => "foo",
                "undefined" => 1,
                "link" => "http://example.com",
                "readonly" => "foo",
                "stored_data" => "one,two,three"
            ]
        );

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("john.doe@example.com", $entity->email);
        Assert::same("defaultValue", $entity->publicProperty);
        Assert::same("http://example.com", $entity->url);
        Assert::same("foo", $entity->readonly);
        Assert::same(["one", "two", "three"], $entity->storedData);
    }

    public function testUnmapEntity()
    {
        $entity = $this->createEntity(
            "Simple",
            [
                "email" => "john.doe@example.com",
                "url" => "http://example.com",
                "empty" => null,
                "storedData" => ["one", "two", "three"],
                "oneToOne" => ["id" => 3]
            ]
        );

        Assert::same(
            [
                "email_address" => $entity->email,
                "link" => $entity->url,
                "empty" => null,
                "stored_data" => "one,two,three"
            ],
            $this->mapper->unmapEntity($entity)
        );
    }

    public function testMapCollection()
    {
        $collection = $this->mapper->mapCollection(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
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
        $entity = $this->createEntity(
            "Simple",
            [
                "email" => "john.doe@example.com",
                "url" => "http://example.com",
                "publicProperty" => "foo"
            ]
        );

        $collection = new \UniMapper\EntityCollection(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $collection[] = $entity;

        Assert::same(
            [['email_address' => $entity->email, 'link' => $entity->url]],
            $this->mapper->unmapCollection($collection)
        );
    }

}

$testCase = new AdapterMapperTest;
$testCase->run();