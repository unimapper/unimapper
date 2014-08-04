<?php

use Tester\Assert,
    UniMapper\Query;

require __DIR__ . '/../bootstrap.php';

class QueryCustomTest extends Tester\TestCase
{
    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;
    
    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }
    
    public function testGet()
    {
        $this->adapterMock->shouldReceive("custom")
            ->with("resource", "query", Query\Custom::METHOD_GET, null, null)
            ->once()
            ->andReturn([]);

        $query = new Query\Custom(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
        Assert::same([], $query->get("query")->execute());
    }
    
    public function testPut()
    {
        $this->adapterMock->shouldReceive("custom")
            ->with("resource", "query", Query\Custom::METHOD_PUT, "application/json", [])
            ->once()
            ->andReturn([]);

        $put = new Query\Custom(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
        $put->put("query", [], "application/json");
        Assert::same([], $put->execute());    
    }
    
    public function testPost()
    {
        $this->adapterMock->shouldReceive("custom")
            ->with("resource", "query", Query\Custom::METHOD_POST, null, [])
            ->once()
            ->andReturn([]);

        $post = new Query\Custom(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
        $post->post("query", []);
        Assert::same([], $post->execute());
    }    

    public function testDelete()
    {
        $this->adapterMock->shouldReceive("custom")
            ->with("resource", "query", Query\Custom::METHOD_DELETE, null, null)
            ->once();

        $delete = new Query\Custom(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
        $delete->delete("query");
        Assert::same(null, $delete->execute());
    }
    
    public function testRaw()
    {
        $this->adapterMock->shouldReceive("custom")
            ->with("resource", ["arg1", "arg2"], Query\Custom::METHOD_RAW, null, null)
            ->once();

        $raw = new Query\Custom(
            new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
        $raw->raw("arg1", "arg2");
        Assert::same(null, $raw->execute());
    }
    
}

$testCase = new QueryCustomTest;
$testCase->run();