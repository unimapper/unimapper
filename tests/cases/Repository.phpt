<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;
use UniMapper\NamingConvention as UNC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class RepositoryTest extends \Tester\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->repository = $this->createRepository("Simple", ["FooAdapter" => $this->adapterMock]);
    }

    private function createRepository($name, array $adapters = [])
    {
        $connection = new \UniMapper\Connection(new \UniMapper\Mapper);
        foreach ($adapters as $adapterName => $adapter) {
            $connection->registerAdapter($adapterName, $adapter);
        }

        $class = UNC::nameToClass($name, UNC::REPOSITORY_MASK);
        return new $class($connection);
    }

    public function testGetName()
    {
        Assert::same("Simple", $this->repository->getName());
    }

    public function testGetEntityName()
    {
        Assert::same("Simple", $this->repository->getEntityName());
    }

    public function testSaveUpdate()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("simple_resource", "simplePrimaryId", 2, ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(true);

        $entity = new Fixtures\Entity\Simple(["id" => 2, "text" => "foo"]);
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Entity\Reflection("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

        $this->repository->save($entity);

        Assert::same(2, $entity->id);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Entity was not successfully updated!
     */
    public function testSaveUpdateFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("simple_resource", "simplePrimaryId", 2, ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(false);

        $entity = new Fixtures\Entity\Simple(["id" => 2, "text" => "foo"]);
        $this->repository->save($entity);
    }

    public function testSaveInsert()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ["text" => "foo"], "simplePrimaryId")
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(3);

        $entity = new Fixtures\Entity\Simple(["simplePrimaryId" => null, "text" => "foo"]);
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Entity\Reflection("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

        $this->repository->save($entity);

        Assert::same(3, $entity->id);
    }

    public function testSaveInvalid()
    {
        $entity = new Fixtures\Entity\Simple(["email" => "invalidemail", "text" => "foo"]);
        $entity->getValidator()->on("email")->addRule(\UniMapper\Validator::EMAIL, "Invalid e-mail format!");

        try {
            $this->repository->save($entity);
        } catch (UniMapper\Exception\ValidatorException $e) {
            $validator = $e->getValidator();
        }

        Assert::isEqual(
            [
                'properties' => [
                    'email' => [
                        new \UniMapper\Validator\Message('Invalid e-mail format!', 1)
                    ]
                ]
            ],
            (array) $validator->getMessages()
        );
    }

    public function testUpdateBy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdate")
            ->once()
            ->with("simple_resource", ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(3);

        Assert::same(
            3,
            $this->repository->updateBy(
                new Fixtures\Entity\Simple(["text" => "foo"]),
                ["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]
            )
        );
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Primary value can not be empty!
     */
    public function testDestroyNoPrimaryValue()
    {
        $this->repository->destroy(new Fixtures\Entity\Simple);
    }

    public function testDestroy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(true);

        $entity = new Fixtures\Entity\Simple(["id" => 1]);
        $this->repository->destroy($entity);
    }

    public function testDestroyFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(false);

        $entity = new Fixtures\Entity\Simple(["id" => 1]);
        Assert::false($this->repository->destroy($entity));
    }

    public function testDestroyBy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(3);

        Assert::same(3, $this->repository->destroyBy(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]));
    }

    public function testFindOne()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(["simplePrimaryId" => 1]);

        $result = $this->repository->findOne(1);

        Assert::type("UniMapper\Entity", $result);
        Assert::same(1, $result->id);
    }

    public function testFindOneNotFound()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(null);

        Assert::false($this->repository->findOne(1));
    }

    public function testFindPrimaries()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createSelect")
            ->with("simple_resource", ['simplePrimaryId', 'text', 'empty', 'link', 'email_address', 'time', 'date', 'ip', 'mark', 'entity', 'readonly', 'stored_data', 'enumeration'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $result = $this->repository->findPrimaries([1, 2]);

        Assert::type("UniMapper\Entity\Collection", $result);
        Assert::same(1, $result[0]->id);
        Assert::same(2, $result[1]->id);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Values can not be empty!
     */
    public function testFindPrimaryNoValues()
    {
        $this->repository->findPrimaries([]);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Method can not be used because entity NoPrimary has no primary property defined!
     */
    public function testFindPrimaryWithNoPrimaryEntity()
    {
        $this->createRepository("NoPrimary")->findPrimaries([]);
    }

}

$testCase = new RepositoryTest;
$testCase->run();