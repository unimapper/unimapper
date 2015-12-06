<?php

use Tester\Assert;
use UniMapper\Convention;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class RepositoryTest extends TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->repository = $this->createRepository("Entity", ["FooAdapter" => $this->adapterMock]);
    }

    private function createRepository($name, array $adapters = [])
    {
        $connection = new \UniMapper\Connection(new \UniMapper\Mapper);
        foreach ($adapters as $adapterName => $adapter) {
            $connection->registerAdapter($adapterName, $adapter);
        }

        $class = Convention::nameToClass($name, Convention::REPOSITORY_MASK);
        return new $class($connection);
    }

    public function testGetName()
    {
        Assert::same("Entity", $this->repository->getName());
    }

    public function testGetEntityName()
    {
        Assert::same("Entity", $this->repository->getEntityName());
    }

    public function testSaveUpdate()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("Entity", "id", 1, ["foo" => "bar", "entity" => [], "collection" => [[]]])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(true);

        $entity = new Entity;
        $entity->id = 1;
        $entity->foo = "bar";
        $entity->entity = new Entity;
        $entity->collection[] = new Entity;
        $entity->assoc[] = new Entity;

        Assert::same($entity, $this->repository->save($entity));
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Entity was not successfully updated!
     */
    public function testSaveUpdateFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("Entity", "id", 1, ["foo" => "bar"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(false);

        $entity = new Entity;
        $entity->id = 1;
        $entity->foo = "bar";

        $this->repository->save($entity);
    }

    public function testSaveInsert()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("Entity", ["foo" => "bar"], "id")
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(1);

        $entity = new Entity;
        $entity->foo = "bar";
        $entity->assoc[] = new Entity;

        Assert::same($entity, $this->repository->save($entity));
        Assert::same(1, $entity->id);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Entity was successfully created but returned primary is empty!
     */
    public function testSaveInsertButEmptyPrimaryReturned()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("Entity", ["foo" => "bar"], "id")
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(null);

        $entity = new Entity;
        $entity->foo = "bar";
        $this->repository->save($entity);
    }

    /**
     * @throws \UniMapper\Exception\ValidatorException
     **/
    public function testSaveWithValidation()
    {
        $entity = new Entity;
        $entity->foo = "bar";
        $entity->getValidator()
            ->on("foo")
            ->addRule(\UniMapper\Validator::EMAIL, "Invalid e-mail format!");

        $this->repository->save($entity);
    }

    public function testUpdateBy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);

        $this->adapterMock->shouldReceive("createUpdate")
            ->once()
            ->with("Entity", ["foo" => "bar"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(2);

        $entity = new Entity;
        $entity->foo = "bar";

        Assert::same(
            2,
            $this->repository->updateBy(
                $entity,
                ["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]
            )
        );
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Primary value can not be empty!
     */
    public function testDestroyNoPrimaryValue()
    {
        $this->repository->destroy(new Entity);
    }

    public function testDestroy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("Entity", "id", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(true);

        $entity = new Entity;
        $entity->id = 1;

        Assert::true($this->repository->destroy($entity));
    }

    public function testDestroyFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("Entity", "id", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(false);

        $entity = new Entity;
        $entity->id = 1;

        Assert::false($this->repository->destroy($entity));
    }

    public function testDestroyBy()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);

        $this->adapterMock->shouldReceive("createDelete")
            ->with("Entity")
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(2);

        Assert::same(
            2,
            $this->repository->destroyBy(
                ["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]
            )
        );
    }

    public function testFindOne()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("Entity", "id", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(["foo" => "bar"]);

        Assert::type("UniMapper\Entity", $result = $this->repository->findOne(1));
        Assert::same("bar", $result->foo);
    }

    public function testFindOneNotFound()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("Entity", "id", 1)
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
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]);

        $this->adapterMock->shouldReceive("createSelect")
            ->with("Entity", ['id', 'foo', 'entity', 'collection'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn([["id" => 1], ["id" => 2]]);

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

/**
 * @adapter FooAdapter
 *
 * @property int      $id         m:primary
 * @property string   $foo
 * @property Entity   $entity
 * @property Entity[] $assoc      m:assoc(type)
 * @property Entity[] $collection
 */
class Entity extends \UniMapper\Entity {}
class EntityRepository extends \UniMapper\Repository {}

class NoPrimary extends \UniMapper\Entity {}
class NoPrimaryRepository extends \UniMapper\Repository {}

$testCase = new RepositoryTest;
$testCase->run();