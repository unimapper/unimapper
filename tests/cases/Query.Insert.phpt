<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryInsertTest extends UniMapper\Tests\TestCase
{

    /** @var \Mockery\Mock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->adapterMock->shouldReceive("getMapping")->once()->andReturn(new UniMapper\Mapping);
    }

    public function testSuccess()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ['text'=>'foo'])
            ->andReturn($adapterQueryMock);

        $this->adapterMock->shouldReceive("execute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn("1");

        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock],
            ["text" => "foo", "oneToOne" => ["id" => 3]]
        );
        Assert::same(1, $query->execute());
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
