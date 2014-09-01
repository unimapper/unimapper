<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityTest extends UniMapper\Tests\TestCase
{

    public function testCreateEntity()
    {
        $reflection = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );

        $entity = $reflection->createEntity(
            ["text" => "foo", "publicProperty" => "foo", "readonly" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->publicProperty);
        Assert::same("foo", $entity->readonly);
    }

    public function testNoAdapterDefined()
    {
        $reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoAdapter");
        Assert::same("UniMapper\Tests\Fixtures\Entity\NoAdapter", $reflection->getClassName());
    }

    /**
     * @throws UniMapper\Exception\PropertyException Property 'id' already defined as public property!
     */
    public function testDuplicatePublicProperty()
    {
        new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\DuplicatePublicProperty");
    }

    public function testNoPropertyDefined()
    {
        $reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoProperty");
        Assert::count(0, $reflection->getProperties());
    }

    public function testSimple()
    {
        $reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::isEqual(
            array('FooAdapter' => new Reflection\Adapter('FooAdapter(resource)', $reflection)),
            $reflection->getAdapterReflection()
        );
        Assert::isEqual(
            array(
                "id" => new Reflection\Entity\Property('integer $id', $reflection),
                "text" => new Reflection\Entity\Property('string $text', $reflection),
                "empty" => new Reflection\Entity\Property('string $empty', $reflection),
                "entity" => new Reflection\Entity\Property('NoAdapter $entity', $reflection),
                "collection" => new Reflection\Entity\Property('NoAdapter[] $collection', $reflection),
            ),
            $reflection->getProperties()
        );
        Assert::isEqual(
            new Reflection\Entity\Property('integer $id m:primary', $reflection),
            $reflection->getPrimaryProperty()
        );
    }

    public function testHasPrimaryProperty()
    {
        $noPrimary = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        Assert::false($noPrimary->hasPrimaryProperty());
        $simple = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        Assert::true($simple->hasPrimaryProperty());
    }

    public function testGetPrimaryProperty()
    {
        $reflection = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        Assert::same("id", $reflection->getPrimaryProperty()->getName());
    }

    /**
     * @throws Exception Primary property not defined in UniMapper\Tests\Fixtures\Entity\NoPrimary!
     */
    public function testGetPrimaryPropertyWithNoPrimary()
    {
        $reflection = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        $reflection->getPrimaryProperty();
    }

}

$testCase = new ReflectionEntityTest;
$testCase->run();