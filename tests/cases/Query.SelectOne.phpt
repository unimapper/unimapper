<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Query;
use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectOneTest extends \Tester\TestCase
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
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        Assert::type(
            "UniMapper\Tests\Fixtures\Entity\Simple",
            $this->createQuery(1)->run($this->connectionMock)
        );
    }

    public function testAssociateManyToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["collection"] instanceof Association\ManyToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::false($this->createQuery(1)->associate("collection")->run($this->connectionMock));
    }

    public function testAssociateManyToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
             ->once()
             ->with($this->adapterQueryMock)
             ->andReturn(["simplePrimaryId" => 1]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1], "AND"]])
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
                    ["simpleId" => 1, "remoteId" => 2],
                    ["simpleId" => 1, "remoteId" => 3]
                ]
            );

        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [2, 3], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);

        $result = $this->createQuery(1)->associate("manyToMany")->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToMany);
        Assert::same(2, $result->manyToMany[0]->id);
        Assert::same(3, $result->manyToMany[1]->id);
    }

    public function testAssociateManyToOneRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["simplePrimaryId" => 1, "remoteId" => 2]);

        $this->adapterQueryMock->shouldReceive("setConditions")->with([["id", "IN", [2], "AND"]])->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createSelect")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 2]]);

        $result = $this->createQuery(1)->associate("manyToOne")->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::same(2, $result->manyToOne->id);
    }

    public function testAssociateManyToManyRemoteNoDominance()
    {
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $this->adapters["RemoteAdapter"]->shouldReceive("createSelectOne")
            ->with("remote_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["remoteId", "IN", [1], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "simple_remote",
                ['remoteId', 'simpleId']
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 2, "remoteId" => 1],
                    ["simpleId" => 3, "remoteId" => 1]
                ]
            );

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("simple_resource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setConditions")
             ->with([["simplePrimaryId", "IN", [2, 3], "AND"]])
             ->once();
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 2], ["simplePrimaryId" => 3]]);

        $result = $this->createQuery(1, "Remote")
            ->associate("manyToManyNoDominance")
            ->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToManyNoDominance);
        Assert::same(2, $result->manyToManyNoDominance[0]->id);
        Assert::same(3, $result->manyToManyNoDominance[1]->id);
    }

    private function createQuery($id, $entity = "Simple")
    {
        return new Query\SelectOne(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\\" . $entity),
            $id
        );
    }

}

$testCase = new QuerySelectOneTest;
$testCase->run();