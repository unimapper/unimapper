<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryBuilderTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\QueryBuilder $builder */
    private $builder;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");

        $this->builder = new \UniMapper\QueryBuilder(new \UniMapper\Mapper);
        $this->builder->registerAdapter("FooAdapter", $this->adapterMock);
    }

    public function testCount()
    {
        Assert::type("UniMapper\Query\Count", $this->builder->count("Simple"));
    }

    public function testSelect()
    {
        Assert::type("UniMapper\Query\Select", $this->builder->select("Simple"));
    }

    public function testSelectOne()
    {
        Assert::type("UniMapper\Query\SelectOne", $this->builder->selectOne("Simple", 1));
    }

    public function testUpdateOne()
    {
        Assert::type("UniMapper\Query\UpdateOne", $this->builder->updateOne("Simple", 1, ["text" => "foo"]));
    }

    public function testUpdate()
    {
        Assert::type("UniMapper\Query\Update", $this->builder->update("Simple", ["text" => "foo"]));
    }

    public function testInsert()
    {
        Assert::type("UniMapper\Query\Insert", $this->builder->insert("Simple", ["text" => "foo"]));
    }

    public function testDelete()
    {
        Assert::type("UniMapper\Query\Delete", $this->builder->delete("Simple"));
    }

    public function testCustomQuery()
    {
        $this->builder->registerQuery("UniMapper\Tests\Fixtures\Query\Custom");
        Assert::type("UniMapper\Tests\Fixtures\Query\Custom", $this->builder->custom("Simple"));
        Assert::same("foo", $this->builder->custom("Simple")->execute());
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