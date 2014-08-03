<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class QueryFindAllTest extends Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }

    public function testSimple()
    {
        $entity1 = new Fixtures\Entity\Simple;
        $entity1->id = 2;
        $entity2 = new Fixtures\Entity\Simple;
        $entity2->id = 3;

        $collection = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->adapters["FooAdapter"]->shouldReceive("findAll")
            ->with(
                "resource",
                ["link", "text", "id"],
                [
                    ["id", ">", 1, "AND"],
                    [
                        [
                            ["text", "LIKE", "%foo", "AND"]
                        ],
                        'OR'
                    ]
                ],
                ["id" => "desc"],
                null,
                null,
                []
            )
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);
        $this->adapters["FooAdapter"]->shouldReceive("mapCollection")->with(get_class($entity1), [["id" => 2], ["id" => 3]])->once()->andReturn($collection);
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $query = new Query\FindAll(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, "url", "text");
        $query->where("id", ">", 1)
                ->orWhereAre(function($query) {
                    $query->where("text", "LIKE", "%foo");
        })->orderBy("id", "DESC");
        $result = $query->execute();

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same(2, count($result));
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[0]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $result[1]);
    }

}

$testCase = new QueryFindAllTest;
$testCase->run();
