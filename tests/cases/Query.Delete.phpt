<?php

use Tester\Assert;
use UniMapper\Query;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryDeleteTest extends TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $connectionMock;

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testOnExecute()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("fooResource")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["fooId" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $query = new Query\Delete(Foo::getReflection());
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);

        Assert::same(2, $query->run($this->connectionMock));
    }

    public function testOnExecuteNoFilter()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("fooResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $query = new Query\Delete(Foo::getReflection());
        $query->run($this->connectionMock);
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int $id m:primary m:map-by(fooId)
 */
class Foo extends \UniMapper\Entity {}

$testCase = new QueryDeleteTest;
$testCase->run();