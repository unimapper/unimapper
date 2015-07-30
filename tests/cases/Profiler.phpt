<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ProfilerTest extends \Tester\TestCase
{

    public function testLog()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")->with("simple_resource")->once()->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")->with($adapterQueryMock)->once()->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($adapterMock);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new UniMapper\Query\Count(
            UniMapper\Entity\Reflection::load("Simple")
        );

        UniMapper\Profiler::startQuery($query);
        UniMapper\Profiler::endQuery($query->run($connectionMock), 1);

        Assert::type("UniMapper\Profiler\Result", UniMapper\Profiler::getResults()[0]);
        Assert::same($query, UniMapper\Profiler::getResults()[0]->query);
        Assert::same(1, UniMapper\Profiler::getResults()[0]->elapsed);
    }

}

$testCase = new ProfilerTest;
$testCase->run();