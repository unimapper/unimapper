<?php

use Tester\Assert;
use UniMapper\Query;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryDeleteTest extends \Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    private $connectionMock;

    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testOnExecute()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->once();
        $this->adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $query = new Query\Delete(
            new Reflection("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        Assert::same(2, $query->run($this->connectionMock));
    }

    public function testOnExecuteNoFilter()
    {
        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn("2");

        $query = new Query\Delete(
            new Reflection("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->run($this->connectionMock);
    }

}

$testCase = new QueryDeleteTest;
$testCase->run();