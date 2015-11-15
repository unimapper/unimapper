<?php

use Tester\Assert;
use UniMapper\Query\Update;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryUpdateTest extends TestCase
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
        $connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $query = new Update(new Reflection("Entity"), []);
        $query->run($connectionMock);
    }

    public function testSuccess()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setFilter")
            ->once()
            ->with(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);

        $this->adapters["FooAdapter"]->shouldReceive("createUpdate")
            ->once()
            ->with("resource", ['foo' => 'bar'])
            ->andReturn($adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn("2");

        $connectionMock = Mockery::mock("UniMapper\Connection");
        $connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);
        $connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $query = new Update(Entity::getReflection(), ["foo" => "bar"]);
        $query->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]]);

        Assert::same(2, $query->run($connectionMock));
    }

}

/**
 * @adapter FooAdapter(resource)
 *
 * @property int    $id  m:primary
 * @property string $foo
 */
class Entity extends \UniMapper\Entity {}

$testCase = new QueryUpdateTest;
$testCase->run();