<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ProfilerTest extends TestCase
{

    public function testLog()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")
            ->with("resource")
            ->once()
            ->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("Foo")
            ->andReturn($adapterMock);

        $query = new UniMapper\Query\Count(
            UniMapper\Entity\Reflection::load("Entity")
        );

        UniMapper\Profiler::startQuery($query);
        UniMapper\Profiler::endQuery($query->run($connectionMock), 1);

        Assert::type(
            "UniMapper\Profiler\Result",
            UniMapper\Profiler::getResults()[0]
        );
        Assert::same($query, UniMapper\Profiler::getResults()[0]->query);
        Assert::same(1, UniMapper\Profiler::getResults()[0]->elapsed);
    }

}

/**
 * @adapter Foo(resource)
 */
class Entity extends \UniMapper\Entity {}

$testCase = new ProfilerTest;
$testCase->run();