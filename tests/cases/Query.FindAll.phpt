<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

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

    public function testSimple()
    {
        $entity1 = new Fixtures\Entity\Simple;
        $entity1->id = 2;
        $entity2 = new Fixtures\Entity\Simple;
        $entity2->id = 3;

        $collection = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->mappers["FooMapper"]->expects("findAll")
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
        $this->mappers["FooMapper"]->expects("mapCollection")->with(get_class($entity1), [["id" => 2], ["id" => 3]])->once()->andReturn($collection);
        $this->mappers["FooMapper"]->freeze();

        $query = new Query\FindAll(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers, "url", "text");
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
