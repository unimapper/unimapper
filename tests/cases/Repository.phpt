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
        $this->adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
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
        $this->repository->registerAdapter(
            new Fixtures\Adapter\Simple("FooAdapter", new UniMapper\Mapping)
        );
        Assert::type("UniMapper\QueryBuilder", $this->repository->query());
    }

    public function testRegisterCustomQuery()
    {
        $this->repository->registerCustomQuery("UniMapper\Tests\Fixtures\Query\Custom");
        $this->repository->registerAdapter(
            new Fixtures\Adapter\Simple("FooAdapter", new UniMapper\Mapping)
        );
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
        $this->adapterMock->shouldReceive("updateOne")->once()->with("simple_resource", "id", 2, ["text" => "foo"]);
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple", ["id" => 2, "text" => "foo"]);
        $this->repository->save($entity);

        Assert::same(2, $entity->id);
    }

    public function testSaveInsert()
    {
        $this->adapterMock->shouldReceive("insert")->once()->with("simple_resource", ["text" => "foo"])->andReturn(["id" => 1]);
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

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
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple");
        $this->repository->delete($entity);
    }

    public function testDelete()
    {
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
        $this->adapterMock->shouldReceive("delete")->with("simple_resource", [["id", "=", 1, "AND"]])->once();
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");

        $this->repository->registerAdapter($this->adapterMock);

        $entity = $this->createEntity("Simple", ["id" => 1]);
        $this->repository->delete($entity);
    }

    public function testFind()
    {
        $this->adapterMock->shouldReceive("find")
            ->with(
                "simple_resource",
                ["id", "text", "empty", "link", "email_address", "time", "ip", "mark", "entity", "readonly", "stored_data"],
                [["text", "LIKE", "foo", "AND"]],
                ["time" => "desc"],
                10,
                20,
                []
            )
            ->once()
            ->andReturn([]);
        $this->adapterMock->shouldReceive("getName")->once()->andReturn("FooAdapter");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $this->repository->registerAdapter($this->adapterMock);
        $result = $this->repository->find(
            [["text", "LIKE", "foo"]],
            [["time", "DESC"]],
            10,
            20
        );

        Assert::type("UniMapper\EntityCollection", $result);
        Assert::count(0, $result);
    }

    public function testFindOne()
    {
        $entity = $this->createEntity("Simple", ["id" => 1, "text" => "foo"]);

        $this->adapterMock->shouldReceive("findOne")
            ->with("simple_resource", "id", $entity->id, [])
            ->once()
            ->andReturn(["id" => $entity->id, "text" => $entity->text]);

        $this->adapterMock->shouldReceive("getMapping")
            ->once()
            ->andReturn(new UniMapper\Mapping);

        $this->adapterMock->shouldReceive("getName")
            ->once()
            ->andReturn("FooAdapter");

        $this->repository->registerAdapter($this->adapterMock);
        $result = $this->repository->findOne($entity->id);

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
        Assert::same($entity->id, $result->id);
        Assert::same($entity->text, $result->text);
    }

    public function testCount()
    {
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
        $this->adapterMock->shouldReceive("getName")
            ->once()
            ->andReturn("FooAdapter");

        $this->adapterMock->shouldReceive("count")
            ->with("simple_resource", [["text", "LIKE", "foo", "AND"]])
            ->once()
            ->andReturn(2);

        $this->repository->registerAdapter($this->adapterMock);

        $result = $this->repository->count([["text", "LIKE", "foo"]]);
        Assert::same(2, $result);
    }

}

$testCase = new RepositoryTest;
$testCase->run();