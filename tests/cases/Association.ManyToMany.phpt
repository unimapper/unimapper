<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AssociationEntities.php';

/**
 * @testCase
 */
class AssociationManyToManyTest extends TestCase
{

    /** @var \Mockery\Mock */
    private $fooAdapterMock;

    /** @var \Mockery\Mock */
    private $barAdapterMock;

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock  */
    private $connectionMock;

    public function setUp()
    {
        $this->fooAdapterMock = Mockery::mock("UniMapper\Adapter");
        $this->barAdapterMock = Mockery::mock("UniMapper\Adapter");
        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testSaveChangesAdd()
    {
        $this->barAdapterMock->shouldReceive("createInsert")
            ->with(
                "Bar",
                ["text" => "foo"],
                "id"
            )
            ->twice()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->twice()
            ->andReturn("2", "3");
        $this->fooAdapterMock->shouldReceive("createManyToManyAdd")
            ->with(
                "Foo",
                "Bar_Foo",
                "Bar",
                "Foo_id",
                "Bar_id",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->add(new Bar(["text" => "foo"]));
        $collection->add(new Bar(["text" => "foo"]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesAttach()
    {
        $this->fooAdapterMock->shouldReceive("createManyToManyAdd")
            ->with(
                "Foo",
                "Bar_Foo",
                "Bar",
                "Foo_id",
                "Bar_id",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->attach(new Bar(["id" => 2]));
        $collection->attach(new Bar(["id" => 3]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesRemove()
    {
        $this->barAdapterMock->shouldReceive("createDelete")
            ->with("Bar")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => ["2", "3"]]]);

        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();
        $this->fooAdapterMock->shouldReceive("createManyToManyRemove")
            ->with(
                "Foo",
                "Bar_Foo",
                "Bar",
                "Foo_id",
                "Bar_id",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->remove(new Bar(["id" => 2]));
        $collection->remove(new Bar(["id" => 3]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesDetach()
    {
        $this->fooAdapterMock->shouldReceive("createManyToManyRemove")
            ->with(
                "Foo",
                "Bar_Foo",
                "Bar",
                "Foo_id",
                "Bar_id",
                \Mockery::mustBe("1"),
                \Mockery::mustBe(["2", "3"])
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->detach(new Bar(["id" => 2]));
        $collection->detach(new Bar(["id" => 3]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesEmptyWithNoChanges()
    {
        Assert::null(
            $this->create()->saveChanges(
                1,
                Mockery::mock("UniMapper\Connection"),
                Bar::createCollection()
            )
        );
    }

    public function testLoad()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["Foo_id" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
            ->once();
        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("Bar_Foo", ['Foo_id', 'Bar_id'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["Foo_id" => 1, "Bar_id" => 3],
                    ["Foo_id" => 2, "Bar_id" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("Bar")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        Assert::same(
            [
                1 => [["id" => 3]],
                2 => [["id" => 4]]
            ],
            $this->create()->load($this->connectionMock, [1, 2])
        );
    }

    public function testLoadNoJoinRecords()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["Foo_id" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
            ->once();
        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("Bar_Foo", ['Foo_id', 'Bar_id'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([]);

        Assert::same(
            [],
            $this->create()->load($this->connectionMock, [1, 2])
        );
    }

    public function testLoadNoTargetRecords()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["Foo_id" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
            ->once();
        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("Bar_Foo", ['Foo_id', 'Bar_id'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["Foo_id" => 1, "Bar_id" => 3],
                    ["Foo_id" => 2, "Bar_id" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("Bar")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([]);

        Assert::same(
            [],
            $this->create()->load($this->connectionMock, [1, 2])
        );
    }

    public function testLoadNoDominance()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("Bar_Foo", ['Foo_id', 'Bar_id'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["Foo_id" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
            ->once();
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["Foo_id" => 1, "Bar_id" => 3],
                    ["Foo_id" => 2, "Bar_id" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("Bar")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["id" => 3], ["id" => 4]]);

        Assert::same(
            [
                1 => [["id" => 3]],
                2 => [["id" => 4]]
            ],
            $this->create(false)->load($this->connectionMock, [1, 2])
        );
    }

    public function testGetKey()
    {
        Assert::same("id", $this->create()->getKey());
    }

    private function create($dominant = true)
    {
        return new Association\ManyToMany(Foo::getReflection(), Bar::getReflection(), [], $dominant);
    }

}

$testCase = new AssociationManyToManyTest;
$testCase->run();