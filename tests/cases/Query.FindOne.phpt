<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryFindOneTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
        $this->adapters["RemoteAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
    }

    public function testNoAssociations()
    {
        $entity = $this->createEntity("Simple", ["id" => 1]);

        $this->adapters["FooAdapter"]->shouldReceive("findOne")
            ->with("simple_resource", "id", 1, [])
            ->once()
            ->andReturn(["id" => 1]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, $entity->id);
        $result = $query->execute();

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
    }

    public function testAssociateHasMany()
    {
        $this->adapters["FooAdapter"]->shouldReceive("findOne")
            ->with(
                "simple_resource",
                "id",
                1,
                Mockery::on(function($arg) {
                    return $arg["collection"] instanceof Reflection\Entity\Property\Association\HasMany;
                })
            )
            ->once()
            ->andReturn(false);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, 1);
        Assert::false($query->associate("collection")->execute());
    }

    public function testAssociateHasManyRemote()
    {
        $this->adapters["FooAdapter"]->shouldReceive("findOne")
            ->with("simple_resource", "id", 1, [])
            ->once()
            ->andReturn(["id" => 1]);
        $this->adapters["FooAdapter"]->shouldReceive("find")
            ->with(
                "simple_remote",
                ['simpleId', 'remoteId'],
                [["simpleId", "IN", [1], "AND"]]
            )
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 1, "remoteId" => 2],
                    ["simpleId" => 1, "remoteId" => 3]
                ]
            );

        $this->adapters["RemoteAdapter"]->shouldReceive("find")
            ->with(
                "remote_resource",
                [],
                [["id", "IN", [2, 3], "AND"]]
            )
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, 1);
        $result = $query->associate("hasMany")->execute();

        Assert::same(1, $result->id);
        Assert::count(2, $result->hasMany);
        Assert::same(2, $result->hasMany[0]->id);
        Assert::same(3, $result->hasMany[1]->id);
    }

    public function testAssociateHasManyRemoteNoDominance()
    {
        $this->adapters["RemoteAdapter"]->shouldReceive("findOne")
            ->with("remote_resource", "id", 1, [])
            ->once()
            ->andReturn(["id" => 1]);

        $this->adapters["FooAdapter"]->shouldReceive("find")
            ->with(
                "simple_remote",
                ['remoteId', 'simpleId'],
                [["remoteId", "IN", [1], "AND"]]
            )
            ->once()
            ->andReturn(
                [
                    ["simpleId" => 2, "remoteId" => 1],
                    ["simpleId" => 3, "remoteId" => 1]
                ]
            );

        $this->adapters["FooAdapter"]->shouldReceive("find")
            ->with(
                "simple_resource",
                [],
                [["id", "IN", [2, 3], "AND"]]
            )
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"), $this->adapters, 1);
        $result = $query->associate("hasManyNoDominance")->execute();

        Assert::same(1, $result->id);
        Assert::count(2, $result->hasManyNoDominance);
        Assert::same(2, $result->hasManyNoDominance[0]->id);
        Assert::same(3, $result->hasManyNoDominance[1]->id);
    }

}

$testCase = new QueryFindOneTest;
$testCase->run();