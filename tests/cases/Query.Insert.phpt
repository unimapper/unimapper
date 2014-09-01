<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryInsertTest extends UniMapper\Tests\TestCase
{

    /** @var \Mockery\Mock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
    }

    public function testSuccess()
    {
        $this->adapterMock->shouldReceive("insert")->once()->andReturn("1");
        
        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock],
            ["text" => "foo"]
        );
        Assert::same(1, $query->execute());
        Assert::same(['text' => 'foo'], $query->getValues());
    }

    /**
     * @throws Exception Nothing to insert!
     */
    public function testNoValues()
    {
        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock],
            []
        );
        $query->execute();
    }

}

$testCase = new QueryInsertTest;
$testCase->run();
