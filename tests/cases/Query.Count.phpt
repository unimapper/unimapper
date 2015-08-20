<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryCountTest extends \Tester\TestCase
{

    public function testOnExecute()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]])->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")->with("simple_resource")->once()->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")->with($adapterQueryMock)->once()->andReturn("1");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($adapterMock);
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new UniMapper\Query\Count(
            new UniMapper\Entity\Reflection("UniMapper\Tests\Fixtures\Entity\Simple")
        );
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        Assert::same(1, $query->run($connectionMock));
    }

}

$testCase = new QueryCountTest;
$testCase->run();