<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class RepositoryTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter\IAdapter");
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");
        $this->adapterMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Adapter\Mapper);

        $this->repository = $this->createRepository("Simple", ["FooAdapter" => $this->adapterMock]);
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
        $this->adapterMock->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(true);

        $entity = $this->createEntity("Simple", ["id" => 2, "text" => "foo"]);
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

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
        $this->adapterMock->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(false);

        $entity = $this->createEntity("Simple", ["id" => 2, "text" => "foo"]);
        $this->repository->save($entity);
    }

    public function testSaveInsert()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(["id" => 1]);

        $entity = $this->createEntity("Simple", ["simplePrimaryId" => null, "text" => "foo"]);
        $entity->text = "foo";
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

        $this->repository->save($entity);

        Assert::same(1, $entity->id);
    }

    public function testSaveInvalid()
    {
        $entity = $this->createEntity(
            "Simple",
            ["email" => "invalidemail", "text" => "foo"]
        );
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

    /**
     * @throws UniMapper\Exception\QueryException Primary value can not be empty!
     */
    public function testDeleteNoPrimaryValue()
    {
        $entity = $this->createEntity("Simple");
        $this->repository->delete($entity);
    }

    public function testDelete()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(true);

        $entity = $this->createEntity("Simple", ["id" => 1]);
        $this->repository->delete($entity);
    }

    public function testDeleteFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(false);

        $entity = $this->createEntity("Simple", ["id" => 1]);
        Assert::false($this->repository->delete($entity));
    }

    public function testCreateEntity()
    {
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity = $this->repository->createEntity(["id" => 3]));
        Assert::same(3, $entity->id);
    }

    public function testCreateCollection()
    {
        Assert::type("UniMapper\EntityCollection", $collection = $this->repository->createCollection([["id" => 3]]));
        Assert::same("UniMapper\Tests\Fixtures\Entity\Simple", $collection->getEntityReflection()->getClassName());
        Assert::same(3, $collection[0]->id);
    }

    public function testFindPrimary()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->once()
            ->with([["simplePrimaryId", "IN", [1, 2], "AND"]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createFind")
            ->with("simple_resource", ['simplePrimaryId', 'text', 'empty', 'link', 'email_address', 'time', 'ip', 'mark', 'entity', 'readonly', 'stored_data'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $result = $this->repository->findPrimaries([1, 2]);

        Assert::type("UniMapper\EntityCollection", $result);
        Assert::same(1, $result[0]->id);
        Assert::same(2, $result[1]->id);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values must be specified!
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