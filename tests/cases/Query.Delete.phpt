<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryDeleteTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
    }

    public function testRun()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($adapterQueryMock);

        $adapterQueryMock->shouldReceive("setConditions")
            ->with([["simplePrimaryId", "=", 1, "AND"]])
            ->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn("2");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new Query\Delete(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->where("id", "=", 1);
        Assert::same(2, $query->run($connectionMock));
    }

    /**
     * @throws UniMapper\Exception\QueryException At least one condition must be set!
     */
    public function testNoConditionGiven()
    {
        $connectionMock = Mockery::mock("UniMapper\Connection");

        $query = new Query\Delete(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->run($connectionMock);
    }

}

$testCase = new QueryDeleteTest;
$testCase->run();