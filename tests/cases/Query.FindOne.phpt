<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class QueryFindOneTest extends Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }

    public function testSuccess()
    {
        $entity = new Fixtures\Entity\Simple;
        $entity->id = 1;

        $this->adapters["FooAdapter"]->shouldReceive("findOne")
            ->with("resource", "id", 1, [])
            ->once()
            ->andReturn(["id" => 1]);
        $this->adapters["FooAdapter"]->shouldReceive("mapEntity")->with(get_class($entity), ["id" => 1])->once()->andReturn($entity);
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, $entity->id);
        $result = $query->execute();

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
    }

}

$testCase = new QueryFindOneTest;
$testCase->run();