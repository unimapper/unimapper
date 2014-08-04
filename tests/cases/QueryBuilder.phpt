<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryBuilderTest extends Tester\TestCase
{
    
    /** @var \UniMapper\QueryBuilder $builder */
    private $builder;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;
    
    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
        $this->builder = new \UniMapper\QueryBuilder(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
    }

    public function testCount()
    {
        Assert::type("UniMapper\Query\Count", $this->builder->count());
    }
    
    public function testFindAll()
    {
        Assert::type("UniMapper\Query\FindAll", $this->builder->findAll());
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
    
    public function testCustom()
    {
        Assert::type("UniMapper\Query\Custom", $this->builder->custom());
    }
    
    public function testDelete()
    {
        Assert::type("UniMapper\Query\Delete", $this->builder->delete());
    }
    
    public function testCustomQuery()
    {
        $this->builder->registerQuery("UniMapper\Tests\Fixtures\Query\Simple");
        Assert::type("UniMapper\Tests\Fixtures\Query\Simple", $this->builder->simple());
    }
    
}

$testCase = new QueryBuilderTest;
$testCase->run();