<?php

use Tester\Assert;
use UniMapper\Query\Update;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryUpdateTest extends \Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
    }

    /**
     * @throws UniMapper\Exception\QueryException Nothing to update!
     */
    public function testNoValues()
    {
        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);

        $query = new Update(new Reflection("UniMapper\Tests\Fixtures\Entity\Simple"), []);
        $query->run($connectionMock);
    }

    public function testSuccess()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["simplePrimaryId" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapters["FooAdapter"]->shouldReceive("createUpdate")
            ->once()
            ->with("simple_resource", ['text'=>'foo'])
            ->andReturn($adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn("2");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getMapper")->once()->andReturn(new UniMapper\Mapper);
        $connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $query = new Update(
            new Reflection("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["text" => "foo", "oneToOne" => ["id" => 3]]
        );
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);
        Assert::same(2, $query->run($connectionMock));
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();
