<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Association,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryFindTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["FooAdapter"]->shouldReceive("createMapping")->once()->andReturn(new UniMapper\Adapter\Mapper);

        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"]->shouldReceive("createMapping")->once()->andReturn(new UniMapper\Adapter\Mapper);

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
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with(
                "simple_resource",
                ["link", "text", "simplePrimaryId"],
                ["simplePrimaryId" => "desc"],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 2], ["simplePrimaryId" => 3]]);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "url", "text");
        $query->where("id", ">", 1)
                ->orWhereAre(function($query) {
                    $query->where("text", "LIKE", "%foo");
        })->orderBy("id", "DESC");
        $result = $query->execute();

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same(2, count($result));
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[0]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[1]);
    }

    public function testAssociateManyToOneRemote()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_resource", ["simplePrimaryId", "remoteId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1, "remoteId" => 3], ["simplePrimaryId" => 2, "remoteId" => 4]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createFind")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "id");
        $result = $query->associate("manyToOne")->execute();

        Assert::count(2, $result);
        Assert::same(3, $result[0]->manyToOne->id);
        Assert::same(4, $result[1]->manyToOne->id);
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

        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with(
                "simple_resource",
                ["simplePrimaryId"],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "id");
        Assert::count(0, $query->associate("collection")->execute());
    }

    public function testAssociateManyToManyRemoteNoRecords()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_resource", ["simplePrimaryId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1, 2], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_remote", ['simpleId', 'remoteId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([]);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "id");
        $result = $query->associate("manyToMany")->execute();

        Assert::count(2, $result);

        Assert::count(0, $result[0]->manyToMany);
        Assert::count(0, $result[1]->manyToMany);
    }

    public function testAssociateManyToManyRemote()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_resource", ["simplePrimaryId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["simpleId", "IN", [1, 2], "AND"]])
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
                    ["simpleId" => 1, "remoteId" => 3],
                    ["simpleId" => 2, "remoteId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["RemoteAdapter"]->shouldReceive("createFind")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "id");
        $result = $query->associate("manyToMany")->execute();

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
        $this->adapters["RemoteAdapter"]->shouldReceive("createFind")
            ->with("remote_resource", ["id"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        $this->adapterQueryMock->shouldReceive("setConditions")
            ->with([["remoteId", "IN", [3, 4], "AND"]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_remote", ['remoteId', 'simpleId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
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
        $this->adapters["FooAdapter"]->shouldReceive("createFind")
            ->with("simple_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $query = new Query\Find(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"), $this->adapters, "id");
        $result = $query->associate("manyToManyNoDominance")->execute();

        Assert::count(2, $result);

        Assert::same(3, $result[0]->id);
        Assert::count(1, $result[0]->manyToManyNoDominance);
        Assert::same(1, $result[0]->manyToManyNoDominance[0]->id);

        Assert::same(4, $result[1]->id);
        Assert::count(1, $result[1]->manyToManyNoDominance);
        Assert::same(2, $result[1]->manyToManyNoDominance[0]->id);
    }

}

$testCase = new QueryFindTest;
$testCase->run();
