<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
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
     * @throws UniMapper\Exception\EntityException Property 'id' already defined as public property!
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

    public function testGetProperties()
    {
        $reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::same(
            array(
                'id',
                'text',
                'empty',
                'url',
                'email',
                'time',
                'year',
                'ip',
                'mark',
                'entity',
                'collection',
                'manyToMany',
                'manyToOne',
                'oneToOne',
                'readonly',
                'storedData',
                'enumeration',
            ),
            array_keys($reflection->getProperties())
        );
    }

    public function testHasPrimary()
    {
        $noPrimary = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        Assert::false($noPrimary->hasPrimary());
        $simple = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\Simple"
        );
        Assert::true($simple->hasPrimary());
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
    public function testGetPrimaryPropertyNotDefined()
    {
        $reflection = new Reflection\Entity(
            "UniMapper\Tests\Fixtures\Entity\NoPrimary"
        );
        $reflection->getPrimaryProperty();
    }

    public function testGetRelatedFiles()
    {
        $simpleRef = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedRef = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteRef = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote");

        Assert::same(
            [
                $nestedRef->getFileName(),
                $simpleRef->getFileName(),
                $remoteRef->getFileName()
            ],
            $simpleRef->getRelatedFiles()
        );

        Assert::same(
            [
                $simpleRef->getFileName(),
                $remoteRef->getFileName(),
                $nestedRef->getFileName()
            ],
            $remoteRef->getRelatedFiles()
        );

        $noAdapterRef = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoAdapter");
        Assert::same([], $noAdapterRef->getRelatedFiles());
    }

}

$testCase = new ReflectionEntityTest;
$testCase->run();