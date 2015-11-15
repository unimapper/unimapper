<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryBuilderTest extends TestCase
{

    private function createBuilder($entity = "Entity")
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
        \UniMapper\QueryBuilder::registerQuery("Custom");
        Assert::type("Custom", $this->createBuilder()->custom());
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Query with name unknown does not exist!
     */
    public function testUnknownQuery()
    {
        $this->createBuilder()->unknown();
    }

}

/**
 * @adapter Foo
 *
 * @property int $id m:primary
 */
class Entity extends \UniMapper\Entity {}

class Custom extends \UniMapper\Query
{
    protected function onExecute(\UniMapper\Connection $connection)
    {
        return "foo";
    }
}

$testCase = new QueryBuilderTest;
$testCase->run();