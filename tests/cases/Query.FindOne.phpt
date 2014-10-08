<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Association,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryFindOneTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapterQueryMock->shouldReceive("getRaw")->once();
    }

    public function testNoAssociations()
    {
        $entity = $this->createEntity("Simple", ["id" => 1]);

        $this->adapters["FooAdapter"]->shouldReceive("createFindOne")
            ->with("simple_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, $entity->id);
        $result = $query->execute();

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
    }

    public function testAssociateManyToMany()
    {
        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["collection"] instanceof Association\ManyToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createFindOne")
            ->with("simple_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, 1);
        Assert::false($query->associate("collection")->execute());
    }

    public function testAssociateManyToManyRemote()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createFindOne")
            ->with("simple_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
             ->once()
             ->with($this->adapterQueryMock)
             ->andReturn(["id" => 1]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_remote", ['simpleId', 'remoteId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 1, "remoteId" => 2],
                    ["simpleId" => 1, "remoteId" => 3]
                ]
            );

        $this->adapters["RemoteAdapter"]->shouldReceive("createFind")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [2, 3], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, 1);
        $result = $query->associate("manyToMany")->execute();

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToMany);
        Assert::same(2, $result->manyToMany[0]->id);
        Assert::same(3, $result->manyToMany[1]->id);
    }

    public function testAssociateManyToOneRemote()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createFindOne")
            ->with("simple_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1, "remoteId" => 2]);

        $this->adapterQueryMock->shouldReceive("setConditions")->with([["id", "IN", [2], "AND"]])->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createFind")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 2]]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, 1);
        $result = $query->associate("manyToOne")->execute();

        Assert::same(1, $result->id);
        Assert::same(2, $result->manyToOne->id);
    }

    public function testAssociateManyToManyRemoteNoDominance()
    {
        $this->adapters["RemoteAdapter"]->shouldReceive("createFindOne")
            ->with("remote_resource", "id", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["remoteId", "IN", [1], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with(
                "simple_remote",
                ['remoteId', 'simpleId']
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 2, "remoteId" => 1],
                    ["simpleId" => 3, "remoteId" => 1]
                ]
            );

        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setConditions")
             ->with([["id", "IN", [2, 3], "AND"]])
             ->once();
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"), $this->adapters, 1);
        $result = $query->associate("manyToManyNoDominance")->execute();

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToManyNoDominance);
        Assert::same(2, $result->manyToManyNoDominance[0]->id);
        Assert::same(3, $result->manyToManyNoDominance[1]->id);
    }

}

$testCase = new QueryFindOneTest;
$testCase->run();