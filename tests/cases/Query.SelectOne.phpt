<?php

use Tester\Assert;
use UniMapper\Query;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property\Option\Assoc;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectOneTest extends TestCase
{

    /** @var \Mockery\Mock */
    private $fooAdapterMock;

    /** @var \Mockery\Mock */
    private $barAdapterMock;

    /** @var \Mockery\Mock */
    private $adapterQueryMock;

    /** @var \Mockery\Mock */
    private $connectionMock;

    public function setUp()
    {
        $this->fooAdapterMock = Mockery::mock("UniMapper\Adapter");
        $this->barAdapterMock = Mockery::mock("UniMapper\Adapter");
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
            ->andReturn($this->fooAdapterMock);

        $this->fooAdapterMock->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(["id" => 1]);

        Assert::type("Foo", $this->createQuery(1)->run($this->connectionMock));
    }

    public function testOnExecuteWithAdapterAssociation()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);

        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["adapterAssociation"] instanceof Assoc;
                })
            )
            ->once();

        $this->fooAdapterMock->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::false(
            $this->createQuery(1)
                ->associate("adapterAssociation")
                ->run($this->connectionMock)
        );
    }

    public function testOnExecuteWithAssociation()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->twice()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->fooAdapterMock->shouldReceive("createSelectOne")
            ->with("fooResource", "fooId", 1)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
             ->once()
             ->with($this->adapterQueryMock)
             ->andReturn(["fooId" => 1]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1]]])
            ->once();
        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("foo_bar", ['foo_fooId', 'bar_barId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ["foo_fooId" => 1, "bar_barId" => 2],
                    ["foo_fooId" => 1, "bar_barId" => 3]
                ]
            );

        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [2, 3]]])
            ->once();
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 2], ["barId" => 3]]);

        $result = $this->createQuery(1)
            ->associate("manyToMany")
            ->run($this->connectionMock);

        Assert::same(1, $result->id);
        Assert::count(2, $result->manyToMany);
        Assert::same(2, $result->manyToMany[0]->id);
        Assert::same(3, $result->manyToMany[1]->id);
    }

    private function createQuery($id)
    {
        return Foo::query()->selectOne($id);
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int   $id                 m:primary m:map-by(fooId)
 * @property Foo[] $adapterAssociation m:assoc(type)
 * @property Bar[] $manyToMany         m:assoc(M:N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
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