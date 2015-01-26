<?php

use Tester\Assert,
    UniMapper\Association,
    UniMapper\Query,
    UniMapper\Cache,
    UniMapper\Mapper,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock */
    private $connectionMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapterQueryMock->shouldReceive("getRaw")->once();

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testOnExecute()
    {
        $entity1 = $this->createEntity("Simple", ["id" => 2]);
        $entity2 = $this->createEntity("Simple", ["id" => 3]);

        $collection = new UniMapper\EntityCollection("Simple");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with(
                [
                    ["simplePrimaryId", ">", 1, "AND"],
                    [
                        [
                            ["text", "LIKE", "%foo", "AND"]
                        ],
                        'OR'
                    ]
                ]
            )
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "simple_resource",
                ["link", "text", "simplePrimaryId"],
                ["simplePrimaryId" => "desc"],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 2], ["simplePrimaryId" => 3]]);

        $result = $this->createQuery()
            ->select("url")
            ->select("text")
            ->where("id", ">", 1)
                ->orWhereAre(function($query) {
                    $query->where("text", "LIKE", "%foo");
                })->orderBy("id", "DESC")
            ->run($this->connectionMock);

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same(2, count($result));
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[0]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[1]);
    }

    public function testOnExecuteWithoutPrimary()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "resource",
                ["text", "empty"],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["text" => "foo"]]);

        $result = $this->createQuery("NoPrimary")->run($this->connectionMock);

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same($result[0]->text, "foo");
    }

    public function testAssociateManyToOneRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", ["simplePrimaryId", "remoteId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1, "remoteId" => 3], ["simplePrimaryId" => 2, "remoteId" => 4]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToOne")
            ->run($this->connectionMock);

        Assert::count(2, $result);
        Assert::same(3, $result[0]->manyToOne->id);
        Assert::same(4, $result[1]->manyToOne->id);
    }

    public function testAssociateManyToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["collection"] instanceof Association\ManyToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "simple_resource",
                ["simplePrimaryId"],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::count(
            0,
            $this->createQuery()
                ->select("id")
                ->associate("collection")
                ->run($this->connectionMock)
        );
    }

    public function testAssociateManyToManyRemoteNoRecords()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", ["simplePrimaryId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1, 2], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_remote", ['simpleId', 'remoteId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToMany")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::count(0, $result[0]->manyToMany);
        Assert::count(0, $result[1]->manyToMany);
    }

    public function testAssociateManyToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", ["simplePrimaryId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1, 2], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_remote", ['simpleId', 'remoteId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 1, "remoteId" => 3],
                    ["simpleId" => 2, "remoteId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToMany")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::same(1, $result[0]->id);
        Assert::count(1, $result[0]->manyToMany);
        Assert::same(3, $result[0]->manyToMany[0]->id);

        Assert::same(2, $result[1]->id);
        Assert::count(1, $result[1]->manyToMany);
        Assert::same(4, $result[1]->manyToMany[0]->id);
    }

    public function testAssociateManyToManyRemoteNoDominance()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource", ["id"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["remoteId", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_remote", ['remoteId', 'simpleId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 1, "remoteId" => 3],
                    ["simpleId" => 2, "remoteId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simplePrimaryId", "IN", [1, 2], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $result = $this->createQuery("Remote")
            ->select("id")
            ->associate("manyToManyNoDominance")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::same(3, $result[0]->id);
        Assert::count(1, $result[0]->manyToManyNoDominance);
        Assert::same(1, $result[0]->manyToManyNoDominance[0]->id);

        Assert::same(4, $result[1]->id);
        Assert::count(1, $result[1]->manyToManyNoDominance);
        Assert::same(2, $result[1]->manyToManyNoDominance[0]->id);
    }

    public function testCachedSave()
    {
        $simpleRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Remote");

        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->with("d886dfb98c2b2a3aa4e74579606919f2")
            ->andReturn(false);
        $cacheMock->shouldReceive("save")
            ->with(
                "d886dfb98c2b2a3aa4e74579606919f2",
                [["simplePrimaryId" => 3], ["simplePrimaryId" => 4]],
                [
                    Cache\ICache::TAGS => ["myTag", Cache\ICache::TAG_QUERY],
                    Cache\ICache::FILES => [
                        $simpleRef->getFileName(),
                        $nestedRef->getFileName(),
                        $remoteRef->getFileName()
                    ]
                ]
            );

        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getCache")->once()->andReturn($cacheMock);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", ["simplePrimaryId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 3], ["simplePrimaryId" => 4]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simplePrimaryId", "IS", 1, "AND"]])
            ->once();

        $result = $this->createQuery("Simple")
            ->select("id")
            ->where("id", "IS", 1)
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\EntityCollection", $result);
        Assert::count(2, $result);
        Assert::same(3, $result[0]->id);
        Assert::same(4, $result[1]->id);
    }

    public function testCachedLoad()
    {
        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->once()
            ->with("d886dfb98c2b2a3aa4e74579606919f2")
            ->andReturn([["simplePrimaryId" => 3], ["simplePrimaryId" => 4]]);

        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getCache")->once()->andReturn($cacheMock);

        $result = $this->createQuery("Simple")
            ->select("id")
            ->where("id", "IS", 1)
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\EntityCollection", $result);
        Assert::count(2, $result);
        Assert::same(3, $result[0]->id);
        Assert::same(4, $result[1]->id);
    }

    public function testSelect()
    {
        Assert::same(["id"], $this->createQuery()->select("id")->selection);
        Assert::same(["id", "text"], $this->createQuery()->select(["id", "text"])->selection);
        Assert::same(["id", "text"], $this->createQuery()->select("id", "text")->selection);
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'undefined' is not defined on entity UniMapper\Tests\Fixtures\Entity\Simple!
     */
    public function testSelectUndefinedProperty()
    {
        $this->createQuery()->select("undefined");
    }

    private function createQuery($entity = "Simple")
    {
        return new Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\\" . $entity)
        );
    }

}

$testCase = new QuerySelectTest;
$testCase->run();
