<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityTest extends Tester\TestCase
{

    /**
     * @throws UniMapper\Exceptions\PropertyException No mapper defined!
     */
    public function testNoMapperDefined()
    {
        new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoMapper");
    }

    /**
     * @throws UniMapper\Exceptions\PropertyException Property 'id' already defined as public property!
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
            array('FooMapper' => new Reflection\Mapper('FooMapper(resource)', $reflection)),
            $reflection->getMapperReflection()
        );
        Assert::isEqual(
            array(
                "id" => new Reflection\Entity\Property('integer $id', $reflection),
                "text" => new Reflection\Entity\Property('string $text', $reflection),
                "empty" => new Reflection\Entity\Property('string $empty', $reflection),
                "entity" => new Reflection\Entity\Property('NoMapper $entity', $reflection),
                "collection" => new Reflection\Entity\Property('NoMapper[] $collection', $reflection),
            ),
            $reflection->getProperties()
        );
        Assert::isEqual(
            new Reflection\Entity\Property('integer $id m:primary', $reflection),
            $reflection->getPrimaryProperty()
        );
    }

}

$testCase = new ReflectionEntityTest;
$testCase->run();