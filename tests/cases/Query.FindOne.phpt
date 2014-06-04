<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class QueryFindOneTest extends Tester\TestCase
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
        $entity = new Fixtures\Entity\Simple;
        $entity->id = 1;

        $this->mappers["FooMapper"]->expects("findOne")
            ->with("resource", "id", 1, [])
            ->once()
            ->andReturn(["id" => 1]);
        $this->mappers["FooMapper"]->expects("mapEntity")->with(get_class($entity), ["id" => 1])->once()->andReturn($entity);
        $this->mappers["FooMapper"]->freeze();

        $query = new Query\FindOne(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers, $entity->id);
        $result = $query->execute();

        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result);
        Assert::true($result->isActive());
    }

}

$testCase = new QueryFindOneTest;
$testCase->run();