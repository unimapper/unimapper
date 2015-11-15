<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Query;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectOneTest extends TestCase
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
        $this->adapters["BarAdapter"] = Mockery::mock("UniMapper\Adapter");

        $this->adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $this->connectionMock = Mockery::mock("UniMapper\Connection");
    }

    public function testOnExecute()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        Assert::type("Foo", $this->createQuery(1)->run($this->connectionMock));
    }

    public function testAssociateManyToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["manyToMany"] instanceof Association\ManyToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::false(
            $this->createQuery(1)
                ->associate("manyToMany")
                ->run($this->connectionMock)
        );
    }

    public function testAssociateManyToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->twice()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
             ->once()
             ->with($this->adapterQueryMock)
             ->andReturn(["fooId" => 1]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1]]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("foo_bar", ['foo_fooId', 'bar_barId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["foo_fooId" => 1, "bar_barId" => 2],
                    ["foo_fooId" => 1, "bar_barId" => 3]
                ]
            );

        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [2, 3]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 2], ["barId" => 3]]);

        $result = $this->createQuery(1)
            ->associate("manyToManyRemote")
            ->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToManyRemote);
        Assert::same(2, $result->manyToManyRemote[0]->id);
        Assert::same(3, $result->manyToManyRemote[1]->id);
    }

    public function testAssociateManyToOneRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->twice()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["fooId" => 1, "barId" => 2]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [2]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 2]]);

        $result = $this->createQuery(1)
            ->associate("manyToOneRemote")
            ->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::same(2, $result->manyToOneRemote->id);
    }

    public function testAssociateManyToManyRemoteNoDominance()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->twice()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["fooId" => 1]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("foo_bar", ['foo_fooId', 'bar_barId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["foo_fooId" => 1, "bar_barId" => 2],
                    ["foo_fooId" => 1, "bar_barId" => 3]
                ]
            );

        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setFilter")
             ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [2, 3]]])
             ->once();
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 2], ["barId" => 3]]);

        $result = $this->createQuery(1)
            ->associate("manyToManyRemoteNoDominance")
            ->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToManyRemoteNoDominance);
        Assert::same(2, $result->manyToManyRemoteNoDominance[0]->id);
        Assert::same(3, $result->manyToManyRemoteNoDominance[1]->id);
    }


    public function testAssociateOneToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["oneToMany"] instanceof Association\OneToMany;
                })
            )
            ->once();

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::false(
            $this->createQuery(1)
                ->associate("oneToMany")
                ->run($this->connectionMock)
        );
    }

    public function testAssociateOneToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->twice()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 100)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(['fooId' => 100]);

        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["fooId" => [\UniMapper\Entity\Filter::EQUAL => [100]]])
            ->once();

        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ['barId' => 2, 'fooId' => 100]
                ]
            );

        $result = $this->createQuery(100)
            ->associate("oneToManyRemote")
            ->run($this->connectionMock);

        Assert::same(100, $result->id);
        Assert::count(1, $result->oneToManyRemote);
        Assert::same(2, $result->oneToManyRemote[0]->id);
    }

    private function createQuery($id)
    {
        return Foo::query()->selectOne($id);
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int   $id                          m:primary m:map-by(fooId)
 * @property Foo[] $manyToMany                  m:assoc(M:N) m:assoc-by(one|foo_foo|two)
 * @property Bar[] $manyToManyRemote            m:assoc(M:N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
 * @property Bar   $manyToOneRemote             m:assoc(N:1) m:assoc-by(barId)
 * @property Bar[] $manyToManyRemoteNoDominance m:assoc(M<N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
 * @property Foo[] $oneToMany                   m:assoc(1:N) m:assoc-by(fooId)
 * @property Bar[] $oneToManyRemote             m:assoc(1:N) m:assoc-by(fooId)
 */
class Foo extends \UniMapper\Entity {}

/**
 * @adapter BarAdapter(barResource)
 *
 * @property int $id m:primary m:map-by(barId)
 */
class Bar extends \UniMapper\Entity {}

$testCase = new QuerySelectOneTest;
$testCase->run();