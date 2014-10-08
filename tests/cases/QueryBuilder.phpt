<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryBuilderTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\QueryBuilder $builder */
    private $builder;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->builder = new \UniMapper\QueryBuilder(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
    }

    public function testCount()
    {
        Assert::type("UniMapper\Query\Count", $this->builder->count());
    }

    public function testFind()
    {
        Assert::type("UniMapper\Query\Find", $this->builder->find());
    }

    public function testFindOne()
    {
        Assert::type("UniMapper\Query\FindOne", $this->builder->findOne(1));
    }

    public function testUpdateOne()
    {
        Assert::type("UniMapper\Query\UpdateOne", $this->builder->updateOne(1, ["text" => "foo"]));
    }

    public function testUpdate()
    {
        Assert::type("UniMapper\Query\Update", $this->builder->update(["text" => "foo"]));
    }

    public function testInsert()
    {
        Assert::type("UniMapper\Query\Insert", $this->builder->insert(["text" => "foo"]));
    }

    public function testDelete()
    {
        Assert::type("UniMapper\Query\Delete", $this->builder->delete());
    }

    public function testCustomQuery()
    {
        $this->builder->registerQuery("UniMapper\Tests\Fixtures\Query\Custom");
        Assert::type("UniMapper\Tests\Fixtures\Query\Custom", $this->builder->custom());
        Assert::same("foo", $this->builder->custom()->execute());
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Query with name unknown does not exist!
     */
    public function testUnknownQuery()
    {
        $this->builder->unknown();
    }

}

$testCase = new QueryBuilderTest;
$testCase->run();