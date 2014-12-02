<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryInsertTest extends UniMapper\Tests\TestCase
{

    /** @var \Mockery\Mock */
    private $adapterMock;

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapterQueryMock->shouldReceive("getRaw")->once();
    }

    public function testSuccess()
    {
        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ['text'=>'foo'])
            ->andReturn($this->adapterQueryMock);

        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock],
            new \UniMapper\Mapper,
            ["text" => "foo", "oneToOne" => ["id" => 3]]
        );
        Assert::same(1, $query->execute());
    }

    public function testNoValues()
    {
        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock],
            new \UniMapper\Mapper,
            []
        );

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", [])
            ->andReturn($this->adapterQueryMock);

        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        Assert::same(1, $query->execute());
    }

}

$testCase = new QueryInsertTest;
$testCase->run();
