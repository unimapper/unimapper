<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Filter;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AssociationEntities.php';

/**
 * @testCase
 */
class AssociationManyToOneTest extends TestCase
{

    /** @var \Mockery\Mock */
    private $fooAdapterMock;

    /** @var \Mockery\Mock */
    private $barAdapterMock;

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock */
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

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["id" => [Filter::EQUAL => [1, 2]]])
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
            [3 => ["id" => 3], 4 => ["id" => 4]],
            $this->create()->load($this->connectionMock, [1, 2])
        );
    }

    public function testSaveChangesAttach()
    {
        $this->fooAdapterMock
            ->shouldReceive("createUpdateOne")
            ->with("Foo", "id", \Mockery::mustBe("1"), \Mockery::mustBe(["Bar_id" => "2"]))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["id" => 2]);
        $entity->attach();

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesAdd()
    {
        $this->barAdapterMock
            ->shouldReceive("createInsert")
            ->with("Bar", ["text" => "foo"], "id")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $this->fooAdapterMock
            ->shouldReceive("createUpdateOne")
            ->with("Foo", "id", \Mockery::mustBe("1"), \Mockery::mustBe(["Bar_id" => "2"]))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

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
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["text" => "foo"]);
        $entity->add();

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesDetach()
    {
        $this->fooAdapterMock
            ->shouldReceive("createUpdateOne")
            ->with("Foo", "id", \Mockery::mustBe("1"), ["Bar_id" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar;
        $entity->detach();

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesRemove()
    {
        $this->barAdapterMock
            ->shouldReceive("createDeleteOne")
            ->with("Bar", "id", \Mockery::mustBe("2"))
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $this->fooAdapterMock
            ->shouldReceive("createUpdateOne")
            ->with("Foo", "id", \Mockery::mustBe("1"), ["Bar_id" => null])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

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
            ->andReturn(new \UniMapper\Mapper());

        $entity = new Bar(["id" => 2]);
        $entity->remove();

        Assert::null($this->create()->saveChanges(1, $this->connectionMock, $entity));
    }

    public function testSaveChangesWithNoChange()
    {
        Assert::null($this->create()->saveChanges(1, $this->connectionMock, new Bar(["id" => 2])));
    }

    /**
     * @throws UniMapper\Exception\AssociationException Target entity must have defined primary for this relation!
     */
    public function testConstructTargetHasNoPrimary()
    {
        $this->create("NoPrimary");
    }

    public function testLoadWithEmptyPrimaries()
    {
        $this->create()->load($this->connectionMock, [null, null]);
    }

    private function create($targetEntity = "Bar")
    {
        return new Association\ManyToOne(Foo::getReflection(), Reflection::load($targetEntity));
    }

}

class NoPrimary extends \UniMapper\Entity {}

$testCase = new AssociationManyToOneTest;
$testCase->run();