<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryCountTest extends UniMapper\Tests\TestCase
{

    public function testCount()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")->with([["simplePrimaryId", "=", 1, "AND"]])->once();
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $adapterMock = Mockery::mock("UniMapper\Adapter");
        $adapterMock->shouldReceive("createCount")->with("simple_resource")->once()->andReturn($adapterQueryMock);
        $adapterMock->shouldReceive("onExecute")->with($adapterQueryMock)->once()->andReturn("1");

        $query = new \UniMapper\Query\Count(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $adapterMock],
            new \UniMapper\Mapper(new UniMapper\EntityFactory)
        );
        $query->where("id", "=", 1);
        Assert::same(1, $query->execute());
    }

}

$testCase = new QueryCountTest;
$testCase->run();