<?php

use Tester\Assert,
    UniMapper\Query\Update,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryUpdateTest extends Tester\TestCase
{

    /** @var \Mockista\Mock */
    private $mapperMock;

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");
    }

    /**
     * @throws UniMapper\Exceptions\QueryException Update is not allowed on primary property 'id'!
     */
    public function testDoNotUpdatePrimary()
    {
        $this->mapperMock->freeze();
        new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock, ["id" => 1]);
    }

    /**
     * @throws UniMapper\Exceptions\QueryException Nothing to update!
     */
    public function testNothingToUpdate()
    {
        $this->mapperMock->expects("unmapEntity")->once()->andReturn([]);
        $this->mapperMock->freeze();

        new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock, []);
    }

    public function testSuccess()
    {
        $this->mapperMock->expects("update")->once()->andReturn("1");
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo"]);
        $this->mapperMock->freeze();

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock, ["text" => "foo"]);
        $query->where("id", "=", 1);
        Assert::same(null, $query->execute());
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();