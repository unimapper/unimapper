<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AssociationManyToManyTest extends \Tester\TestCase
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
    }

    public function testSaveChangesAdd()
    {
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createInsert")
            ->with("remote_resource", ['text' => 'foo'], "id")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("onExecute")
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
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $collection = new UniMapper\Entity\Collection("Remote");
        $collection->add(new Fixtures\Entity\Remote(["text" => "foo"]));

        $association = new Association\ManyToMany("manyToMany", Reflection\Loader::load("Simple"), Reflection\Loader::load("Remote"), ["simpleId", "simple_remote", "remoteId"]);

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

    public function testSaveChangesRemove()
    {
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createDeleteOne")
            ->with("remote_resource", "id", 3)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("onExecute")
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
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getAdapter")->once()->with("RemoteAdapter")->andReturn($this->adapters["RemoteAdapter"]);

        $collection = new UniMapper\Entity\Collection("Remote");
        $collection->remove(new Fixtures\Entity\Remote(["id" => 3, "text" => "foo"]));

        $association = new Association\ManyToMany("manyToMany", Reflection\Loader::load("Simple"), Reflection\Loader::load("Remote"), ["simpleId", "simple_remote", "remoteId"]);

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

}

$testCase = new AssociationManyToManyTest;
$testCase->run();