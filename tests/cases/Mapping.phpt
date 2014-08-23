<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class MappingTest extends Tester\TestCase
{

    /** @var \UniMapper\Mapping */
    private $mapping;

    public function setUp()
    {
        $this->mapping = new UniMapper\Mapping;
    }

    public function testMapEntity()
    {
        $entity = $this->mapping->mapEntity(
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
        $entity = new Fixtures\Entity\Simple;
        $entity->email = "john.doe@example.com";
        $entity->url = "http://example.com";
        $entity->empty = null;
        $entity->storedData = ["one", "two", "three"];

        Assert::same(
            [
                "email_address" => $entity->email,
                "link" => $entity->url,
                "empty" => null,
                "stored_data" => "one,two,three"
            ],
            $this->mapping->unmapEntity($entity)
        );
    }

    public function testMapCollection()
    {
        $collection = $this->mapping->mapCollection(
            new Reflection\Entity("Simple"),
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
            new Reflection\Entity("Simple")
        );
        $collection[] = $entity;

        Assert::same(
            [['email_address' => $entity->email, 'link' => $entity->url]],
            $this->mapping->unmapCollection($collection)
        );
    }

}

$testCase = new MappingTest;
$testCase->run();