<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryCountTest extends Tester\TestCase
{
    
    public function testCount()
    {
        $adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
        $adapterMock->shouldReceive("count")->with("resource", [["id", "=", 1, "AND"]])->once()->andReturn("1");

        $query = new \UniMapper\Query\Count(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $adapterMock]
        );
        $query->where("id", "=", 1);
        Assert::same(1, $query->execute());        
    }
    
}

$testCase = new QueryCountTest;
$testCase->run();