<?php

use Tester\Assert,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

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
        $entity1 = new Fixtures\Entity\Simple;
        $entity1->id = 2;
        $entity2 = new Fixtures\Entity\Simple;
        $entity2->id = 3;

        $collection = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->mapperMock->expects("unmapSelection")->once()->andReturn(["link", "text"]);
        $this->mapperMock->expects("unmapOrderBy")->once()->andReturn(["id" => "DESC"]);
        $this->mapperMock->expects("findAll")
            ->with("resource", ["link", "text"], [["id", ">", 1, "AND"]], ["id" => "DESC"], null, null)
            ->once()
            ->andReturn([["id" => 2], ["id" => 3]]);
        $this->mapperMock->expects("mapCollection")->with(get_class($entity1), [["id" => 2], ["id" => 3]])->once()->andReturn($collection);
        $this->mapperMock->freeze();

        $query = new Query\FindAll(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mapperMock, "url", "text");
        $query->where("id", ">", 1)->orderBy("id", "DESC");
        $result = $query->execute();

        Assert::type("Unimapper\EntityCollection", $result);
        Assert::same(2, count($result));
        foreach ($result as $entity) {
            Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
            Assert::true($entity->isActive());
        }
    }

}

$testCase = new QueryFindAllTest;
$testCase->run();