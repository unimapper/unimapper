<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AssociationEntities.php';

/**
 * @testCase
 */
class AssociationOneToManyTest extends TestCase
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

    public function testLoad()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("Bar")
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
                    ['id' => 3, 'Foo_id' => 1],
                    ['id' => 4, 'Foo_id' => 2]
                ]
            );

        Assert::same(
            [
                1 => [['id' => 3, 'Foo_id' => 1]],
                2 => [['id' => 4, 'Foo_id' => 2]]
            ],
            $this->create()->load($this->connectionMock, [1, 2])
        );
    }

    public function testSaveChangesAdd()
    {
        $this->barAdapterMock->shouldReceive("createInsert")
            ->with(
                "Bar",
                \Mockery::mustBe([
                    "text" => "foo",
                    "Foo_id" => "1"
                ]),
                "id"
            )
            ->twice()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->twice();

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
        $this->barAdapterMock->shouldReceive("createUpdate")
            ->with("Bar", \Mockery::mustBe(["Foo_id" => "1"]))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(
                \Mockery::mustBe(
                    [
                        "Foo_id" => [
                            \UniMapper\Entity\Filter::EQUAL => ["1", "2"]
                        ]
                    ]
                )
            );

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->attach(new Bar(["id" => 1]));
        $collection->attach(new Bar(["id" => 2]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesDetach()
    {
        $this->barAdapterMock->shouldReceive("createUpdate")
            ->with("Bar", ["Foo_id" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(
                \Mockery::mustBe(
                    [
                        "Foo_id" => [
                            \UniMapper\Entity\Filter::EQUAL => ["1", "2"]
                        ]
                    ]
                )
            );

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->detach(new Bar(["id" => 1]));
        $collection->detach(new Bar(["id" => 2]));

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $collection));
    }

    public function testSaveChangesRemove()
    {
        $this->barAdapterMock->shouldReceive("createDelete")
            ->with("Bar")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(
                \Mockery::mustBe(
                    [
                        "Foo_id" => [
                            \UniMapper\Entity\Filter::EQUAL => ["1", "2"]
                        ]
                    ]
                )
            );

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper);

        $collection = Bar::createCollection();
        $collection->remove(new Bar(["id" => 1]));
        $collection->remove(new Bar(["id" => 2]));

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

    private function create()
    {
        return new Association\OneToMany(Foo::getReflection(), Bar::getReflection());
    }

}

$testCase = new AssociationOneToManyTest;
$testCase->run();