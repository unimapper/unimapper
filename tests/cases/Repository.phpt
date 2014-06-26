<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class RepositoryTest extends Tester\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    public function setUp()
    {
        $this->repository = new Fixtures\Repository\SimpleRepository;
    }

    public function testGetName()
    {
        Assert::same("Simple", $this->repository->getName());
    }

    public function testGetEntityName()
    {
        Assert::same("Simple", $this->repository->getEntityName());
    }

    public function testQuery()
    {
        $this->repository->registerMapper(
            new Fixtures\Mapper\Simple("FooMapper")
        );
        Assert::type("UniMapper\QueryBuilder", $this->repository->query());
    }

    public function testCreateEntity()
    {
        // Autodetect entity
        $entity = $this->repository->createEntity(
            null,
            ["text" => "foo", "publicProperty" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->publicProperty);

        // Force entity
        $nestedEntity = $this->repository->createEntity(
            "Nested",
            ["text" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedEntity);
        Assert::same("foo", $nestedEntity->text);
    }

    /**
     * @throws UniMapper\Exceptions\RepositoryException You must set one mapper at least!
     */
    public function testMapperRequired()
    {
        $this->repository->query();
    }

}

$testCase = new RepositoryTest;
$testCase->run();