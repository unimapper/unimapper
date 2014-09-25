<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class MappingTest extends UniMapper\Tests\TestCase
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
        $entity = $this->createEntity(
            "Simple",
            [
                "email" => "john.doe@example.com",
                "url" => "http://example.com",
                "empty" => null,
                "storedData" => ["one", "two", "three"]
            ]
        );

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
            $this->mapping->unmapCollection($collection)
        );
    }

    public function testUnmapConditions()
    {
        Assert::same(
            array(
                array('link', 'LIKE', 'url', 'AND'),
                array(
                    array(
                        array('email_address', 'LIKE', '%email_foo%', 'AND'),
                        array('email_address', 'LIKE', '%another_email_foo', 'OR')
                    ),
                    'OR'
                )
            ),
            $this->mapping->unmapConditions(
                [
                    ["url", "LIKE", "url", "AND"],
                    [
                        [
                            ["email", "LIKE", "%email_foo%", "AND"],
                            ["email", "LIKE", "%another_email_foo", "OR"]
                        ],
                        "OR"
                    ]
                ],
                new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
            )
        );
    }

}

$testCase = new MappingTest;
$testCase->run();