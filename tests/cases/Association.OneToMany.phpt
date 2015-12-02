<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AssociationOneToManyTest extends TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["BarAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testSaveChangesAdd()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createInsert")
            ->with(
                "barResource",
                [
                    "text" => "foo",
                    "foo_fooId" => 1
                ],
                "barId"
            )
            ->twice()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->twice();

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $collection = Bar::createCollection();
        $collection->add(new Bar(["text" => "foo"]));
        $collection->add(new Bar(["text" => "foo"]));

        $association = new Association\OneToMany(
            "oneToMany",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId"]
        );

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

    public function testSaveChangesAttach()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createUpdate")
            ->with("barResource", ["foo_fooId" => 1])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]);

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $collection = Bar::createCollection();
        $collection->attach(new Bar(["id" => 1]));
        $collection->attach(new Bar(["id" => 2]));

        $association = new Association\OneToMany(
            "oneToMany",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId"]
        );

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

    public function testSaveChangesDetach()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createUpdate")
            ->with("barResource", ["foo_fooId" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]);

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $collection = Bar::createCollection();
        $collection->detach(new Bar(["id" => 1]));
        $collection->detach(new Bar(["id" => 2]));

        $association = new Association\OneToMany(
            "oneToMany",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId"]
        );

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

    public function testSaveChangesRemove()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createDelete")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]]);

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $collection = Bar::createCollection();
        $collection->remove(new Bar(["id" => 1]));
        $collection->remove(new Bar(["id" => 2]));

        $association = new Association\OneToMany(
            "oneToMany",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId"]
        );

        Assert::null($association->saveChanges(1, $connectionMock, $collection));
    }

    public function testSaveChangesEmptyWithNoChanges()
    {
        $association = new Association\OneToMany(
            "oneToMany",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId"]
        );

        Assert::null(
            $association->saveChanges(
                1,
                Mockery::mock("UniMapper\Connection"),
                Bar::createCollection()
            )
        );
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int $id m:primary m:map-by(fooId)
 */
class Foo extends \UniMapper\Entity {}

/**
 * @adapter BarAdapter(barResource)
 *
 * @property int    $id   m:primary m:map-by(barId)
 * @property string $text
 */
class Bar extends \UniMapper\Entity {}

$testCase = new AssociationOneToManyTest;
$testCase->run();