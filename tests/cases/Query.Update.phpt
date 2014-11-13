<?php

use Tester\Assert,
    UniMapper\Query\Update,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryUpdateTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["FooAdapter"]->shouldReceive("createMapper")
            ->once()
            ->andReturn(new UniMapper\Adapter\Mapper);
    }

    /**
     * @throws UniMapper\Exception\QueryException Nothing to update!
     */
    public function testNoValues()
    {
        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, []);
        $query->execute();
    }

    public function testSuccess()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->once()
            ->with([["simplePrimaryId", "=", 1, "AND"]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapters["FooAdapter"]->shouldReceive("createUpdate")
            ->once()
            ->with("simple_resource", ['text'=>'foo'])
            ->andReturn($adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn("2");

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, ["text" => "foo", "oneToOne" => ["id" => 3]]);
        $query->where("id", "=", 1);
        Assert::same(2, $query->execute());
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();
