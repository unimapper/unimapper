<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures,
    UniMapper\Cache\ICache;

require __DIR__ . '/../bootstrap.php';

class RepositoryTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->repository = new Fixtures\Repository\SimpleRepository;

        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
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
        $this->repository->registerAdapter($this->adapterMock);
        Assert::type("UniMapper\QueryBuilder", $this->repository->query());
    }

    public function testRegisterCustomQuery()
    {
        $this->repository->registerCustomQuery("UniMapper\Tests\Fixtures\Query\Custom");
        $this->repository->registerAdapter($this->adapterMock);

        Assert::same("foo", $this->repository->query()->custom()->execute());
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Registered custom query must be instance of Unimapper\Query\Custom!
     */
    public function testRegisterCustomQueryFailed()
    {
        $this->repository->registerCustomQuery("UniMapper\Query\Find");
    }

    public function testCreateEntity()
    {
        // Autodetect entity
        $entity = $this->repository->createEntity(
            ["text" => "foo", "publicProperty" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->publicProperty);

        // Force entity
        $nestedEntity = $this->repository->createEntity(
            ["text" => "foo"],
            "Nested"
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedEntity);
        Assert::same("foo", $nestedEntity->text);
    }

    public function testCreateEntityFromCache()
    {
        $simpleRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Remote");

        $cacheMock = Mockery::mock("UniMapper\Tests\Fixtures\Cache\CustomCache");
        $cacheMock->shouldReceive("load")->with("UniMapper\Tests\Fixtures\Entity\Simple")->andReturn(false);
        $cacheMock->shouldReceive("save")->with(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            Mockery::type("UniMapper\Reflection\Entity"),
            [
                ICache::FILES => [
                    $simpleRef->getFileName(),
                    $nestedRef->getFileName(),
                    $remoteRef->getFileName()
                ],
                ICache::TAGS => [ICache::TAG_REFLECTION]
            ]
        );

        $this->repository->setCache($cacheMock);
        $this->repository->createEntity();
    }

    public function testCreateCollection()
    {
        // Autodetect entity
        $collection = $this->repository->createCollection(
            [["text" => "foo", "publicProperty" => "foo"]]
        );
        Assert::type("UniMapper\EntityCollection", $collection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $collection[0]);
        Assert::same("foo", $collection[0]->text);
        Assert::same("foo", $collection[0]->publicProperty);

        // Force entity
        $nestedCollection = $this->repository->createCollection(
            [["text" => "foo"]],
            "Nested"
        );

        Assert::type("UniMapper\EntityCollection", $nestedCollection);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedCollection[0]);
        Assert::same("foo", $nestedCollection[0]->text);
    }

    public function testSaveUpdate()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("simple_resource", "id", 2, ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock);

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple", ["id" => 2, "text" => "foo"]);
        $this->repository->save($entity);

        Assert::same(2, $entity->id);
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

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple", ["id" => null, "text" => "foo"]);
        $entity->text = "foo";
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
     * @throws UniMapper\Exception\RepositoryException Primary value in entity 'Simple' must be set!
     */
    public function testDeletNoPrimaryValue()
    {
        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple");
        $this->repository->delete($entity);
    }

    public function testDelete()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "=", 1, "AND"]])
            ->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("execute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple", ["id" => 1]);
        $this->repository->delete($entity);
    }

}

$testCase = new RepositoryTest;
$testCase->run();