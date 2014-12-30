<?php

use Tester\Assert,
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

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapterQueryMock->shouldReceive("getRaw")->once();
    }

    public function testNoAssociations()
    {
        $entity1 = $this->createEntity("Simple", ["id" => 2]);
        $entity2 = $this->createEntity("Simple", ["id" => 3]);

        $collection = new UniMapper\EntityCollection($entity1->getReflection());
        $collection[] = $entity1;
        $collection[] = $entity2;

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
            ->execute();

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same(2, count($result));
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[0]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[1]);
    }

    public function testAssociateManyToOneRemote()
    {
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
            ->execute();

        Assert::count(2, $result);
        Assert::same(3, $result[0]->manyToOne->id);
        Assert::same(4, $result[1]->manyToOne->id);
    }

    public function testAssociateManyToMany()
    {
        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["collection"] instanceof Reflection\Association\ManyToMany;
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
                ->execute()
        );
    }

    public function testAssociateManyToManyRemoteNoRecords()
    {
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
            ->execute();

        Assert::count(2, $result);

        Assert::count(0, $result[0]->manyToMany);
        Assert::count(0, $result[1]->manyToMany);
    }

    public function testAssociateManyToManyRemote()
    {
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
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToMany")
            ->execute();

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
            ->with("simple_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $result = $this->createQuery("Remote")
            ->select("id")
            ->associate("manyToManyNoDominance")
            ->execute();

        Assert::count(2, $result);

        Assert::same(3, $result[0]->id);
        Assert::count(1, $result[0]->manyToManyNoDominance);
        Assert::same(1, $result[0]->manyToManyNoDominance[0]->id);

        Assert::same(4, $result[1]->id);
        Assert::count(1, $result[1]->manyToManyNoDominance);
        Assert::same(2, $result[1]->manyToManyNoDominance[0]->id);
    }

    public function testCached()
    {
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

        $simpleRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Simple");
        $nestedRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Nested");
        $remoteRef = new ReflectionClass("UniMapper\Tests\Fixtures\Entity\Remote");;

        $cacheMock = Mockery::mock("UniMapper\Tests\Fixtures\Cache\CustomCache");
        $cacheMock->shouldReceive("load")
            ->with("2184d949c54f44268355da2ec0ad9b0e")
            ->andReturn(false);
        $cacheMock->shouldReceive("save")
            ->with(
                "2184d949c54f44268355da2ec0ad9b0e",
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

        $query = $this->createQuery("Simple")
            ->select("id")
            ->where("id", "IS", 1);
        $query->setCache($cacheMock);
        $query->cached(true, [Cache\ICache::TAGS => ["myTag"]])->execute();
    }

    private function createQuery($entity = "Simple")
    {
        return new Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\\" . $entity),
            $this->adapters,
            new Mapper(new \UniMapper\EntityFactory)
        );
    }

}

$testCase = new QuerySelectTest;
$testCase->run();
