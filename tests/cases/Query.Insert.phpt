<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class QueryInsertTest extends Tester\TestCase
{

    /** @var \Mockista\Mock */
    private $mapperMock;

    public function setUp()
    {
        $mockista = new \Mockista\Registry;
        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");
    }

    public function testSuccess()
    {
        $this->mapperMock->expects("insert")->once()->andReturn("1");
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo"]);
        $this->mapperMock->expects("mapValue")->once()->andReturn(1);
        $this->mapperMock->freeze();

        $query = new \UniMapper\Query\Insert(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            $this->mapperMock, ["text" => "foo", "readonly" => "readonlytest"]
        );
        Assert::same(1, $query->execute());
        Assert::same(['text' => 'foo'], $query->getValues());
    }

}

$testCase = new QueryInsertTest;
$testCase->run();