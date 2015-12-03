<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AssociationManyToManyTest extends TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock  */
    private $connectionMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["BarAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testSaveChangesAdd()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createInsert")
            ->with(
                "barResource",
                ["text" => "foo"],
                "barId"
            )
            ->twice()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->twice()
            ->andReturn("2", "3");
        $this->adapters["FooAdapter"]
            ->shouldReceive("createManyToManyAdd")
            ->with(
                "fooResource",
                "foo_bar",
                "barResource",
                "foo_fooId",
                "bar_barId",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->add(new Bar(["text" => "foo"]));
        $collection->add(new Bar(["text" => "foo"]));

        $association = new Association\ManyToMany(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId", "foo_bar", "bar_barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesAttach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createManyToManyAdd")
            ->with(
                "fooResource",
                "foo_bar",
                "barResource",
                "foo_fooId",
                "bar_barId",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->attach(new Bar(["id" => 2]));
        $collection->attach(new Bar(["id" => 3]));

        $association = new Association\ManyToMany(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId", "foo_bar", "bar_barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesRemove()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createDelete")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => ["2", "3"]]]);

        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();
        $this->adapters["FooAdapter"]
            ->shouldReceive("createManyToManyRemove")
            ->with(
                "fooResource",
                "foo_bar",
                "barResource",
                "foo_fooId",
                "bar_barId",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->remove(new Bar(["id" => 2]));
        $collection->remove(new Bar(["id" => 3]));

        $association = new Association\ManyToMany(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId", "foo_bar", "bar_barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesDetach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createManyToManyRemove")
            ->with(
                "fooResource",
                "foo_bar",
                "barResource",
                "foo_fooId",
                "bar_barId",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->detach(new Bar(["id" => 2]));
        $collection->detach(new Bar(["id" => 3]));

        $association = new Association\ManyToMany(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId", "foo_bar", "bar_barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesEmptyWithNoChanges()
    {
        $association = new Association\ManyToMany(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["foo_fooId", "foo_bar", "bar_barId"]
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
 * @property int $id m:primary m:map-by(fooId) m:map-filter(in|out)
 */
class Foo extends \UniMapper\Entity
{
    public static function out($val)
    {
        return (string) $val;
    }

    public static function in($val)
    {
        return (int) $val;
    }
}

/**
 * @adapter BarAdapter(barResource)
 *
 * @property int    $id   m:primary m:map-by(barId) m:map-filter(in|out)
 * @property string $text m:map(text_unmapped)
 */
class Bar extends \UniMapper\Entity
{
    public static function out($val)
    {
        return (string) $val;
    }

    public static function in($val)
    {
        return (int) $val;
    }
}

$testCase = new AssociationManyToManyTest;
$testCase->run();