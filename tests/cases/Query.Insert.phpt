<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryInsertTest extends TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testSuccess()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createInsert")
            ->once()
            ->with("fooResource", ['text_unmapped' => 'foo'], "fooId")
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $query = new \UniMapper\Query\Insert(
            Foo::getReflection(),
            ["text" => "foo", "oneToOne" => ["id" => 3]]
        );
        Assert::same(1, $query->run($connectionMock));
    }

    public function testNoValues()
    {
        $query = new \UniMapper\Query\Insert(Foo::getReflection(), []);

        $this->adapters["FooAdapter"]->shouldReceive("createInsert")
            ->once()
            ->with("fooResource", [], "fooId")
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($this->adapterQueryMock)
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        Assert::same(1, $query->run($connectionMock));
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int    $id   m:primary m:map-by(fooId)
 * @property string $text m:map-by(text_unmapped)
 */
class Foo extends \UniMapper\Entity {}

$testCase = new QueryInsertTest;
$testCase->run();