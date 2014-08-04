<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryDeleteTest extends Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }

    public function testSuccess()
    {
        $this->adapters["FooAdapter"]->shouldReceive("delete")->with("resource", [["id", "=", 1, "AND"]])->once();

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