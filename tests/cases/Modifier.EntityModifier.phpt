<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class ModifierEntityModifierTest extends UniMapper\Tests\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
    }

    public function testManyToOneAttach()
    {
        $this->adapters["FooAdapter"]
            ->shouldReceive("createUpdateOne")
            ->with("simple_resource", "simplePrimaryId", 1, ["remoteId" => 2])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]
            ->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(null);

        $source = new Fixtures\Entity\Simple(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"));
        $target = new Fixtures\Entity\Remote(new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote"));
        $target->id = 2;
        $source->manyToOne()->attach($target);

        Assert::null($source->manyToOne()->save($this->adapters["FooAdapter"], $this->adapters["RemoteAdapter"], 1));
    }

}

$testCase = new ModifierEntityModifierTest;
$testCase->run();