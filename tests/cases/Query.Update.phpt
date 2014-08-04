<?php

use Tester\Assert,
    UniMapper\Query\Update,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryUpdateTest extends Tester\TestCase
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
        $this->adapters["FooAdapter"]->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
        $this->adapters["FooAdapter"]->shouldReceive("update")->once()->andReturn("1");

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->adapters, ["text" => "foo"]);
        $query->where("id", "=", 1);
        Assert::same(null, $query->execute());
        Assert::same(['text' => 'foo'], $query->getValues());
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();
