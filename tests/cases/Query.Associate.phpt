<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class QueryAssociateTest extends UniMapper\Tests\TestCase
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

    public function testAddManyToManyRemote()
    {
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createMapper")
            ->once()
            ->andReturn(new UniMapper\Adapter\Mapper);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createInsert")
            ->with("remote_resource", ['text' => 'foo'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(2);
        $this->adapters["FooAdapter"]
            ->shouldReceive("createModifyManyToMany")
            ->with(
                Mockery::type("UniMapper\Association\ManyToMany"),
                1,
                [2],
                \UniMapper\Adapter\IAdapter::ASSOC_ADD
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $targetEntity = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $targetEntity->text = "foo";

        $sourceEntity = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $sourceEntity->manyToMany()->add($targetEntity);

        $query = new \UniMapper\Query\Associate(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            $this->adapters,
            1,
            $sourceEntity->manyToMany()
        );
        Assert::null($query->execute());
    }

    public function testRemoveManyToManyRemote()
    {
        $this->adapterQueryMock->shouldReceive("setConditions")->with([["id", "=", 3, "AND"]]);

        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createMapper")
            ->once()
            ->andReturn(new UniMapper\Adapter\Mapper);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createDelete")
            ->with("remote_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(2);
        $this->adapters["FooAdapter"]
            ->shouldReceive("createModifyManyToMany")
            ->with(
                Mockery::type("UniMapper\Association\ManyToMany"),
                1,
                [3],
                \UniMapper\Adapter\IAdapter::ASSOC_REMOVE
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $targetEntity = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $targetEntity->id = 3;
        $targetEntity->text = "foo";

        $sourceEntity = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $sourceEntity->manyToMany()->remove($targetEntity);

        $query = new \UniMapper\Query\Associate(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            $this->adapters,
            1,
            $sourceEntity->manyToMany()
        );
        Assert::null($query->execute());
    }

    public function testAttachManyToOneRemote()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("simple_resource", "simplePrimaryId", 1, ["remoteId" => 2])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $source = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $target = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $target->id = 2;
        $source->manyToOne()->attach($target);

        $query = new \UniMapper\Query\Associate(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            $this->adapters,
            1,
            $source->manyToOne()
        );
        Assert::null($query->execute());
    }

}

$testCase = new QueryAssociateTest;
$testCase->run();