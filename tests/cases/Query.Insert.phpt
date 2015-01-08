<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryInsertTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapterQueryMock->shouldReceive("getRaw")->once();
    }

    public function testSuccess()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ['text'=>'foo'])
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["text" => "foo", "oneToOne" => ["id" => 3]]
        );
        Assert::same(1, $query->run($connectionMock));
    }

    public function testNoValues()
    {
        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            []
        );

        $this->adapters["FooAdapter"]->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", [])
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        Assert::same(1, $query->run($connectionMock));
    }

}

$testCase = new QueryInsertTest;
$testCase->run();
