<?php

use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class CacheTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Tests\Fixtures\Repository\SimpleRepository */
    private $repository;

    /** @var \Mockery\Mock */
    private $cacheMock;

    public function setUp()
    {
        Tester\Environment::$checkAssertions = false;

        $this->repository = new Fixtures\Repository\SimpleRepository;
        $this->cacheMock = Mockery::mock("UniMapper\Tests\Fixtures\Cache\CustomCache[load,save,remove]");
        $this->repository->setCache($this->cacheMock);
    }

    public function testLoadEntityReflection()
    {
        $this->cacheMock->shouldReceive("load")
            ->once()
            ->with("UniMapper-Reflection-Entity-Simple");

        $simpleEntityReflection = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedEntityReflection = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteEntityReflection = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Remote");

        $this->cacheMock->shouldReceive("save")
            ->once()
            ->with(
                "UniMapper-Reflection-Entity-Simple",
                Mockery::type("UniMapper\Reflection\Entity"),
                [
                    $nestedEntityReflection->getFileName(),
                    $simpleEntityReflection->getFileName(),
                    $remoteEntityReflection->getFileName()
                ]
            );

        $this->repository->createEntity();
    }

}

$testCase = new CacheTest;
$testCase->run();