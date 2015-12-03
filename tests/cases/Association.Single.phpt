<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AssociationSingleTest extends TestCase
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
        $this->adapters["BarAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testSaveChangesAttach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("fooResource", "fooId", \Mockery::mustBe("1"), \Mockery::mustBe(["barId" => "2"]))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["id" => 2]);
        $entity->attach();

        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesAdd()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createInsert")
            ->with("barResource", ["text" => "foo"], "barId")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("fooResource", "fooId", \Mockery::mustBe("1"), \Mockery::mustBe(["barId" => "2"]))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

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
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["text" => "foo"]);
        $entity->add();

        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesDetach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("fooResource", "fooId", \Mockery::mustBe("1"), ["barId" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar;
        $entity->detach();

        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesRemove()
    {
        $this->adapters["BarAdapter"]
            ->shouldReceive("createDeleteOne")
            ->with("barResource", "barId", \Mockery::mustBe("2"))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("fooResource", "fooId", \Mockery::mustBe("1"), ["barId" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

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
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["id" => 2]);
        $entity->remove();

        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesWithNoChange()
    {
        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );

        Assert::null($association->saveChanges(1, $this->connectionMock, new Bar(["id" => 2])));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Only entity with primary can save changes!
     */
    public function testSaveChangesNoPrimary()
    {
        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );
        $association->saveChanges(1, $this->connectionMock, new NoPrimary);
    }

    public function testLoadWithEmptyPrimaries()
    {
        $association = new Association\ManyToOne(
            "propertyName",
            Foo::getReflection(),
            Bar::getReflection(),
            ["barId"]
        );
        $association->load($this->connectionMock, [null, null]);
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

class NoPrimary extends \UniMapper\Entity {}

$testCase = new AssociationSingleTest;
$testCase->run();