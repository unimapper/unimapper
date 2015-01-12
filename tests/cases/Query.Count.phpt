<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryCountTest extends UniMapper\Tests\TestCase
{

    public function testRun()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")->with([["simplePrimaryId", "=", 1, "AND"]])->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")->with("simple_resource")->once()->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")->with($adapterQueryMock)->once()->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($adapterMock);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new UniMapper\Query\Count(
            new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->where("id", "=", 1);
        Assert::same(1, $query->run($connectionMock));
    }

}

$testCase = new QueryCountTest;
$testCase->run();