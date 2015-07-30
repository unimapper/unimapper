<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class MapperTest extends \Tester\TestCase
{

    /** @var \UniMapper\Mapper */
    private $mapper;

    public function setUp()
    {
        $this->mapper = new UniMapper\Mapper;
    }

    public function testMapEntity()
    {
        $entity = $this->mapper->mapEntity(
            "Simple",
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

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Traversable value can not be mapped to scalar!
     */
    public function testMapValueArrayToString()
    {
        $this->mapper->mapValue(
            Reflection\Loader::load("Simple")->getProperty("text"),
            []
        );
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Traversable value can not be mapped to scalar!
     */
    public function testMapValueObjectToString()
    {
        $this->mapper->mapValue(
            Reflection\Loader::load("Simple")->getProperty("text"),
            new stdClass
        );
    }

    public function testUnmapEntity()
    {
        $entity = new Fixtures\Entity\Simple(
            [
                "email" => "john.doe@example.com",
                "url" => "http://example.com",
                "empty" => null,
                "storedData" => ["one", "two", "three"],
                "oneToOne" => ["id" => 3],
                "readonly" => "readonlyValue"
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
            "Simple",
            [
                [
                    "email_address" => "john.doe@example.com",
                    "publicProperty" => "foo",
                    "undefined" => 1,
                    "readonly" => "foo"
                ]
            ]
        );
        Assert::type("UniMapper\Entity\Collection", $collection);
        Assert::count(1, $collection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $collection[0]);
        Assert::same("john.doe@example.com", $collection[0]->email);
        Assert::same("defaultValue", $collection[0]->publicProperty);
        Assert::same("foo", $collection[0]->readonly);
    }

    public function testUnmapCollection()
    {
        $entity = new Fixtures\Entity\Simple(
            [
                "email" => "john.doe@example.com",
                "url" => "http://example.com",
                "publicProperty" => "foo"
            ]
        );

        $collection = new UniMapper\Entity\Collection("Simple");
        $collection[] = $entity;

        Assert::same(
            [['email_address' => $entity->email, 'link' => $entity->url]],
            $this->mapper->unmapCollection($collection)
        );
    }

}

$testCase = new MapperTest;
$testCase->run();