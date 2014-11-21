<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class AssociationManyToOneTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter\IAdapter");
        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter\IAdapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testModifyAttach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("simple_resource", "simplePrimaryId", 1, ["remoteId" => 2])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("execute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $source = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $target = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $target->id = 2;
        $source->manyToOne()->attach($target);

        Assert::null($source->manyToOne()->modify(1, $this->adapters["FooAdapter"], $this->adapters["RemoteAdapter"]));
    }

}

$testCase = new AssociationManyToOneTest;
$testCase->run();