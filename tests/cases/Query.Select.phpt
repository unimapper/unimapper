<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Query;
use UniMapper\Cache;
use UniMapper\Mapper;
use UniMapper\Entity\Reflection;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectTest extends \Tester\TestCase
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
        $entity1 = new Fixtures\Entity\Simple(["id" => 2]);
        $entity2 = new Fixtures\Entity\Simple(["id" => 3]);

        $collection = new UniMapper\Entity\Collection("Simple");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "simple_resource",
                ["link", "text", "simplePrimaryId"],
                [],
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
            ->run($this->connectionMock);

        Assert::type("Unimapper\Entity\Collection", $result);
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

        Assert::type("Unimapper\Entity\Collection", $result);
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource", [])
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simpleId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simpleId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["remoteId" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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
            ->with("35b84606bf241dfbcd314adcb85c1945")
            ->andReturn(false);
        $cacheMock->shouldReceive("save")
            ->with(
                "35b84606bf241dfbcd314adcb85c1945",
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->once();

        $result = $this->createQuery("Simple")
            ->select("id")
            ->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\Entity\Collection", $result);
        Assert::count(2, $result);
        Assert::same(3, $result[0]->id);
        Assert::same(4, $result[1]->id);
    }

    public function testCachedLoad()
    {
        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->once()
            ->with("35b84606bf241dfbcd314adcb85c1945")
            ->andReturn([["simplePrimaryId" => 3], ["simplePrimaryId" => 4]]);

        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getCache")->once()->andReturn($cacheMock);

        $result = $this->createQuery("Simple")
            ->select("id")
            ->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\Entity\Collection", $result);
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

    public function testAssociateOnoToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["oneToMany"] instanceof Association\OneToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        $this->createQuery()->associate("oneToMany")->run($this->connectionMock);
    }

    public function testAssociateOnoToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["oneToManyRemote"] instanceof Association\OneToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([['simplePrimaryId' => 100], ['simplePrimaryId' => 101], ['simplePrimaryId' => 102]]);

        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => [100, 101, 102]]])
            ->once();

        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ['id' => 2, 'simplePrimaryId' => 100],
                    ['id' => 3, 'simplePrimaryId' => 100],
                    ['id' => 4, 'simplePrimaryId' => 101]
                ]
            );

        $result = $this->createQuery()->associate("oneToManyRemote")->run($this->connectionMock);
        Assert::count(3, $result);
        Assert::count(2, $result[0]->oneToManyRemote);
        Assert::count(1, $result[1]->oneToManyRemote);
        Assert::count(0, $result[2]->oneToManyRemote);
        Assert::same(2, $result[0]->oneToManyRemote[0]->id);
        Assert::same(3, $result[0]->oneToManyRemote[1]->id);
        Assert::same(4, $result[1]->oneToManyRemote[0]->id);
    }

    private function createQuery($entity = "Simple")
    {
        return new Query\Select(
            new Reflection("UniMapper\Tests\Fixtures\Entity\\" . $entity)
        );
    }

}

$testCase = new QuerySelectTest;
$testCase->run();
