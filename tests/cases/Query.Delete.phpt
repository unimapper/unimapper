<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryFindAllTest extends Tester\TestCase
{

    /** @var \Mockista\Mock */
    private $mapperMock;

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");
    }

    public function testSuccess()
    {
        $this->mapperMock->expects("delete")->with("resource", [["id", "=", 1, "AND"]])->once();
        $this->mapperMock->freeze();

        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock);
        $query->where("id", "=", 1);
        Assert::null($query->execute());
    }

    /**
     * @throws UniMapper\Exceptions\QueryException At least one condition must be set!
     */
    public function testNoConditionGiven()
    {
        $this->mapperMock->freeze();
        $query = new Query\Delete(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock);
        $query->execute();
    }

}

$testCase = new QueryFindAllTest;
$testCase->run();