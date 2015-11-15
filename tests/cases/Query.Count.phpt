<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryCountTest extends TestCase
{

    public function testOnExecute()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->with(["fooId" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->once();

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")
            ->with("fooResource")
            ->once()
            ->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($adapterMock);
        $connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $query = new UniMapper\Query\Count(Foo::getReflection());
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        Assert::same(1, $query->run($connectionMock));
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int $id m:primary m:map-by(fooId)
 */
class Foo extends \UniMapper\Entity {}

$testCase = new QueryCountTest;
$testCase->run();