<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryDeleteTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
    }

    public function testSuccess()
    {
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")
            ->once()
            ->andReturn(new UniMapper\Mapping);

        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->adapters["FooAdapter"]->shouldReceive("createDelete")
            ->with("simple_resource")
            ->once()
            ->andReturn($adapterQueryMock);

        $adapterQueryMock->shouldReceive("setConditions")
            ->with([["id", "=", 1, "AND"]])
            ->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(null);

        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters);
        $query->where("id", "=", 1);
        Assert::null($query->execute());
    }

    /**
     * @throws UniMapper\Exception\QueryException At least one condition must be set!
     */
    public function testNoConditionGiven()
    {
        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters);
        $query->execute();
    }

}

$testCase = new QueryDeleteTest;
$testCase->run();