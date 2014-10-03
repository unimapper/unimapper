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
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }

    /**
     * @throws UniMapper\Exception\QueryException Update is not allowed on primary property 'id'!
     */
    public function testDoNotUpdatePrimary()
    {
        new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, ["id" => 1]);
    }

    /**
     * @throws UniMapper\Exception\QueryException Nothing to update!
     */
    public function testNoValues()
    {
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, []);
        $query->execute();
    }

    public function testSuccess()
    {
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")
            ->once()
            ->andReturn(new UniMapper\Mapping);

        $this->adapters["FooAdapter"]->shouldReceive("update")
            ->once()
            ->with("simple_resource", ['text'=>'foo'], [["id", "=", 1, "AND"]])
            ->andReturn("1");

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, ["text" => "foo", "oneToOne" => ["id" => 3]]);
        $query->where("id", "=", 1);
        Assert::same(null, $query->execute());
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();
