<?php

use Tester\Assert,
    UniMapper\Query\Update,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryUpdateTest extends Tester\TestCase
{

    /** @var array */
    private $mappers = [];

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mappers["FooMapper"] = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mappers["FooMapper"]->expects("getName")->once()->andReturn("FooMapper");
    }

    /**
     * @throws UniMapper\Exceptions\QueryException Update is not allowed on primary property 'id'!
     */
    public function testDoNotUpdatePrimary()
    {
        $this->mappers["FooMapper"]->freeze();
        new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers, ["id" => 1]);
    }

    /**
     * @throws UniMapper\Exceptions\QueryException Nothing to update!
     */
    public function testNothingToUpdate()
    {
        $this->mappers["FooMapper"]->expects("unmapEntity")->once()->andReturn([]);
        $this->mappers["FooMapper"]->freeze();

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers, ["readonly" => "foo"]);
        $query->execute();
    }

    public function testSuccess()
    {
        $this->mappers["FooMapper"]->expects("update")->once()->andReturn("1");
        $this->mappers["FooMapper"]->expects("unmapEntity")->once()->andReturn(["text" => "foo", "readonly" => "readonlytest"]);
        $this->mappers["FooMapper"]->freeze();

        $query = new Update(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"), $this->mappers, ["text" => "foo", 'readonly' => 'readonlytest']);
        $query->where("id", "=", 1);
        Assert::same(null, $query->execute());
        Assert::same(['text' => 'foo'], $query->getValues());
    }

}

$testCase = new QueryUpdateTest;
$testCase->run();
