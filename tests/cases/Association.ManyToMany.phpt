<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class AssociationManyToManyTest extends UniMapper\Tests\TestCase
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

    public function testModifyAdd()
    {
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Adapter\Mapper);
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("createInsert")
            ->with("remote_resource", ['text' => 'foo'])
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

        $targetEntity = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $targetEntity->text = "foo";

        $sourceEntity = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $sourceEntity->manyToMany()->add($targetEntity);

        Assert::null($sourceEntity->manyToMany()->modify(1, $this->adapters["FooAdapter"], $this->adapters["RemoteAdapter"]));
    }

    public function testModifyRemove()
    {
        $this->adapters["RemoteAdapter"]
            ->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Adapter\Mapper);
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

        $targetEntity = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $targetEntity->id = 3;
        $targetEntity->text = "foo";

        $sourceEntity = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $sourceEntity->manyToMany()->remove($targetEntity);

        Assert::null($sourceEntity->manyToMany()->modify(1, $this->adapters["FooAdapter"], $this->adapters["RemoteAdapter"]));
    }

}

$testCase = new AssociationManyToManyTest;
$testCase->run();