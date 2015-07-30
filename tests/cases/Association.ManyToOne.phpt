<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Entity\Reflection;
use UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class AssociationManyToOneTest extends \Tester\TestCase
{

    /** @var array $adapters */
    private $adapters = [];

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock */
    private $connectionMock;

    public function setUp()
    {
        $this->adapters["FooAdapter"] = Mockery::mock("UniMapper\Adapter");
        $this->adapters["RemoteAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testSaveChangesAttach()
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

        $this->connectionMock->shouldReceive("getAdapter")->once()->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);

        $entity = new Fixtures\Entity\Remote(["id" => 2]);
        $entity->attach();

        $association = new Association\ManyToOne("manyToOne", Reflection::load("Simple"), Reflection::load("Remote"), ["remoteId"]);

        Assert::null($association->saveChanges(1, $this->connectionMock, $entity));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Only entity with primary can save changes!
     */
    public function testSaveChangesNoPrimary()
    {
        $association = new Association\ManyToOne("manyToOne", Reflection::load("Simple"), Reflection::load("Remote"), ["remoteId"]);
        $association->saveChanges(1, $this->connectionMock, new Fixtures\Entity\NoPrimary);
    }

    public function testLoadWithEmptyPrimaries()
    {
        $association = new Association\ManyToOne("manyToOne", Reflection::load("Simple"), Reflection::load("Remote"), ["remoteId"]);
        $association->load($this->connectionMock, [null, null]);
    }

}

$testCase = new AssociationManyToOneTest;
$testCase->run();