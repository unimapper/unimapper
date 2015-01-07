<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryBuilderTest extends UniMapper\Tests\TestCase
{

    private function createBuilder($entity = "Simple")
    {
        return new UniMapper\QueryBuilder($entity);
    }

    public function testCount()
    {
        Assert::type("UniMapper\Query\Count", $this->createBuilder()->count());
    }

    public function testSelect()
    {
        Assert::type("UniMapper\Query\Select", $this->createBuilder()->select());
    }

    public function testSelectOne()
    {
        Assert::type("UniMapper\Query\SelectOne", $this->createBuilder()->selectOne(1));
    }

    public function testUpdateOne()
    {
        Assert::type("UniMapper\Query\UpdateOne", $this->createBuilder()->updateOne(1, ["text" => "foo"]));
    }

    public function testUpdate()
    {
        Assert::type("UniMapper\Query\Update", $this->createBuilder()->update(["text" => "foo"]));
    }

    public function testInsert()
    {
        Assert::type("UniMapper\Query\Insert", $this->createBuilder()->insert(["text" => "foo"]));
    }

    public function testDelete()
    {
        Assert::type("UniMapper\Query\Delete", $this->createBuilder()->delete());
    }

    public function testCustomQuery()
    {
        \UniMapper\QueryBuilder::registerQuery("UniMapper\Tests\Fixtures\Query\Custom");
        Assert::type("UniMapper\Tests\Fixtures\Query\Custom", $this->createBuilder()->custom());
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Query with name unknown does not exist!
     */
    public function testUnknownQuery()
    {
        $this->createBuilder()->unknown();
    }

}

$testCase = new QueryBuilderTest;
$testCase->run();