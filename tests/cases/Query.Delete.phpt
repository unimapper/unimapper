<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryFindAllTest extends Tester\TestCase
{

    /** @var array */
    private $mappers = [];

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mappers["FooMapper"] = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mappers["FooMapper"]->expects("getName")->once()->andReturn("FooMapper");
    }

    public function testSuccess()
    {
        $this->mappers["FooMapper"]->expects("delete")->with("resource", [["id", "=", 1, "AND"]])->once();
        $this->mappers["FooMapper"]->freeze();

        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers);
        $query->where("id", "=", 1);
        Assert::null($query->execute());
    }

    /**
     * @throws UniMapper\Exceptions\QueryException At least one condition must be set!
     */
    public function testNoConditionGiven()
    {
        $this->mappers["FooMapper"]->freeze();
        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers);
        $query->execute();
    }

}

$testCase = new QueryFindAllTest;
$testCase->run();