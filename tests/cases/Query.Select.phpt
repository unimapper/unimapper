<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Query;
use UniMapper\Cache;
use UniMapper\Mapper;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectTest extends TestCase
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
        $entity1 = new Foo(["id" => 2]);
        $entity2 = new Foo(["id" => 3]);

        $collection = new UniMapper\Entity\Collection("Foo");
        $collection[] = $entity1;
        $collection[] = $entity2;

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["text_unmapped", "fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 2], ["fooId" => 3]]);

        $result = $this->createQuery()
            ->select("text")
            ->run($this->connectionMock);

        Assert::type("Unimapper\Entity\Collection", $result);
        Assert::same(2, count($result));
        Assert::type("Foo", $result[0]);
        Assert::type("Foo", $result[1]);
    }

    public function testAssociateManyToOneRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["fooId", "text_unmapped", "barId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 1, "barId" => 3], ["fooId" => 2, "barId" => 4]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 3], ["barId" => 4]]);

        $result = $this->createQuery()
            ->select("id", "text")
            ->associate("manyToOneRemote")
            ->run($this->connectionMock);

        Assert::count(2, $result);
        Assert::same(3, $result[0]->manyToOneRemote->id);
        Assert::same(4, $result[1]->manyToOneRemote->id);
    }

    public function testAssociateManyToMany()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
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

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "fooResource",
                ["fooId"],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::count(
            0,
            $this->createQuery()
                ->select("id")
                ->associate("manyToMany")
                ->run($this->connectionMock)
        );
    }

    public function testAssociateManyToManyRemoteNoRecords()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["fooId", "text_unmapped"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 1], ["fooId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
            ->once();
        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("foo_bar", ['foo_fooId', 'bar_barId'])
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([]);

        $result = $this->createQuery()
            ->select("id", "text")
            ->associate("manyToManyRemote")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::count(0, $result[0]->manyToManyRemote);
        Assert::count(0, $result[1]->manyToManyRemote);
    }

    public function testAssociateManyToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 1], ["fooId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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
                    ["foo_fooId" => 1, "bar_barId" => 3],
                    ["foo_fooId" => 2, "bar_barId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 3], ["barId" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToManyRemote")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::same(1, $result[0]->id);
        Assert::count(1, $result[0]->manyToManyRemote);
        Assert::same(3, $result[0]->manyToManyRemote[0]->id);

        Assert::same(2, $result[1]->id);
        Assert::count(1, $result[1]->manyToManyRemote);
        Assert::same(4, $result[1]->manyToManyRemote[0]->id);
    }

    public function testAssociateManyToManyRemoteNoDominance()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 1], ["fooId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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
                    ["foo_fooId" => 1, "bar_barId" => 3],
                    ["foo_fooId" => 2, "bar_barId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 3], ["barId" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToManyRemoteNoDominance")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::same(1, $result[0]->id);
        Assert::count(1, $result[0]->manyToManyRemoteNoDominance);
        Assert::same(3, $result[0]->manyToManyRemoteNoDominance[0]->id);

        Assert::same(2, $result[1]->id);
        Assert::count(1, $result[1]->manyToManyRemoteNoDominance);
        Assert::same(4, $result[1]->manyToManyRemoteNoDominance[0]->id);
    }

    public function testCachedSave()
    {
        $reflectionClass = new ReflectionClass("Foo");

        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->with("1467971bc97a9e255c92bc5e2c4904a3")
            ->andReturn(false);
        $cacheMock->shouldReceive("save")
            ->with(
                "1467971bc97a9e255c92bc5e2c4904a3",
                [["fooId" => 3], ["fooId" => 4]],
                [
                    Cache\ICache::TAGS => ["myTag"],
                    Cache\ICache::FILES => [$reflectionClass->getFileName()]
                ]
            );

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getCache")
            ->once()
            ->andReturn($cacheMock);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ["fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 3], ["fooId" => 4]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["fooId" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->once();

        $result = $this->createQuery()
            ->select("id")
            ->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\Entity\Collection", $result);
        Assert::count(2, $result);
        Assert::same(3, $result[0]->id);
        Assert::same(4, $result[1]->id);
    }

    public function testCachedLoad()
    {
        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->once()
            ->with("1467971bc97a9e255c92bc5e2c4904a3")
            ->andReturn([["fooId" => 3], ["fooId" => 4]]);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getCache")
            ->once()
            ->andReturn($cacheMock);

        $result = $this->createQuery()
            ->select("id")
            ->setFilter(["id" => [\UniMapper\Entity\Filter::EQUAL => 1]])
            ->cached(true, [Cache\ICache::TAGS => ["myTag"]])
            ->run($this->connectionMock);

        Assert::type("UniMapper\Entity\Collection", $result);
        Assert::count(2, $result);
        Assert::same(3, $result[0]->id);
        Assert::same(4, $result[1]->id);
    }

    public function testAssociateOnoToMany()
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

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        $this->createQuery()->associate("oneToMany")->run($this->connectionMock);
    }

    public function testAssociateOnoToManyRemote()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->adapters["BarAdapter"]);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([['fooId' => 10], ['fooId' => 11], ['fooId' => 12]]);

        $this->adapters["BarAdapter"]->shouldReceive("createSelect")
            ->with("barResource", [], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["fooId" => [\UniMapper\Entity\Filter::EQUAL => [10, 11, 12]]])
            ->once();

        $this->adapters["BarAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(
                [
                    ['id' => 2, 'fooId' => 10],
                    ['id' => 3, 'fooId' => 10],
                    ['id' => 4, 'fooId' => 11]
                ]
            );

        $result = $this->createQuery()
            ->associate("oneToManyRemote")
            ->run($this->connectionMock);

        Assert::count(3, $result);
        Assert::count(2, $result[0]->oneToManyRemote);
        Assert::count(1, $result[1]->oneToManyRemote);
        Assert::count(0, $result[2]->oneToManyRemote);
        Assert::same(2, $result[0]->oneToManyRemote[0]->id);
        Assert::same(3, $result[0]->oneToManyRemote[1]->id);
        Assert::same(4, $result[1]->oneToManyRemote[0]->id);
    }

    public function testCreateSelection()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with("fooResource", ['fooId', 'text_unmapped'], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->createQuery()->run($this->connectionMock);
    }

    public function testCreateSelectionComplex()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->adapters["FooAdapter"]);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $this->adapters["FooAdapter"]->shouldReceive("createSelect")
            ->with(
                "fooResource",
                [
                    'text_unmapped',
                    'fooId'
                ],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->adapters["FooAdapter"]->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["text_unmapped" => [\UniMapper\Entity\Filter::EQUAL => "foo"]])
            ->once();
        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["manyToOne"] instanceof Association\ManyToOne;
                })
            )
            ->once();

        $this->createQuery()->associate("manyToOne")
            ->select("text")
            ->where(["text" => [\UniMapper\Entity\Filter::EQUAL => "foo"]])
            ->run($this->connectionMock);
    }

    private function createQuery()
    {
        return Foo::query()->select();
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int    $id                          m:primary m:map-by(fooId)
 * @property string $text                        m:map-by(text_unmapped)
 * @property Foo[]  $manyToMany                  m:assoc(M:N) m:assoc-by(one|foo_foo|two)
 * @property Bar[]  $manyToManyRemote            m:assoc(M:N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
 * @property Foo    $manyToOne                   m:assoc(N:1) m:assoc-by(fooId)
 * @property Bar    $manyToOneRemote             m:assoc(N:1) m:assoc-by(barId)
 * @property Bar[]  $manyToManyRemoteNoDominance m:assoc(M<N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
 * @property Foo[]  $oneToMany                   m:assoc(1:N) m:assoc-by(fooId)
 * @property Bar[]  $oneToManyRemote             m:assoc(1:N) m:assoc-by(fooId)
 */
class Foo extends \UniMapper\Entity {}

/**
 * @adapter BarAdapter(barResource)
 *
 * @property int $id m:primary m:map-by(barId)
 */
class Bar extends \UniMapper\Entity {}

$testCase = new QuerySelectTest;
$testCase->run();