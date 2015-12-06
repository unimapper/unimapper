<?php

use Tester\Assert;
use UniMapper\Association;
use UniMapper\Query;
use UniMapper\Cache;
use UniMapper\Mapper;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property\Option\Assoc;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectTest extends TestCase
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
            ->andReturn($this->fooAdapterMock);

        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("fooResource", ["text_unmapped", "fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->fooAdapterMock->shouldReceive("onExecute")
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

    public function testOnExecuteWithAdapterAssociation()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
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

        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with(
                "fooResource",
                ["fooId"],
                [],
                null,
                null
            )
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn(false);

        Assert::count(
            0,
            $this->createQuery()
                ->select("id")
                ->associate("adapterAssociation")
                ->run($this->connectionMock)
        );
    }

    public function testOnExecuteWithAssociation()
    {
        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->twice()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("BarAdapter")
            ->andReturn($this->barAdapterMock);

        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("fooResource", ["fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["fooId" => 1], ["fooId" => 2]]);

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["foo_fooId" => [\UniMapper\Entity\Filter::EQUAL => [1, 2]]])
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
                    ["foo_fooId" => 1, "bar_barId" => 3],
                    ["foo_fooId" => 2, "bar_barId" => 4]
                ]
            );

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["barId" => [\UniMapper\Entity\Filter::EQUAL => [3, 4]]])
            ->once();
        $this->barAdapterMock->shouldReceive("createSelect")
            ->with("barResource")
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->barAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once()
            ->andReturn([["barId" => 3], ["barId" => 4]]);

        $result = $this->createQuery()
            ->select("id")
            ->associate("manyToMany")
            ->run($this->connectionMock);

        Assert::count(2, $result);

        Assert::same(1, $result[0]->id);
        Assert::count(1, $result[0]->manyToMany);
        Assert::same(3, $result[0]->manyToMany[0]->id);

        Assert::same(2, $result[1]->id);
        Assert::count(1, $result[1]->manyToMany);
        Assert::same(4, $result[1]->manyToMany[0]->id);
    }

    public function testCachedSave()
    {
        $reflectionClass = new ReflectionClass("Foo");

        $cacheMock = Mockery::mock("UniMapper\Cache\ICache");
        $cacheMock->shouldReceive("load")
            ->with("f6f90e5cc3cbc700edc3c6860388559e")
            ->andReturn(false);
        $cacheMock->shouldReceive("save")
            ->with(
                "f6f90e5cc3cbc700edc3c6860388559e",
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
            ->andReturn($this->fooAdapterMock);
        $this->connectionMock->shouldReceive("getCache")
            ->once()
            ->andReturn($cacheMock);

        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("fooResource", ["fooId"], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);
        $this->fooAdapterMock->shouldReceive("onExecute")
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
            ->with("f6f90e5cc3cbc700edc3c6860388559e")
            ->andReturn([["fooId" => 3], ["fooId" => 4]]);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new Mapper);
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")->andReturn($this->fooAdapterMock);
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

    public function testCreateSelection()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $this->fooAdapterMock->shouldReceive("createSelect")
            ->with("fooResource", ['fooId', 'text_unmapped'], [], null, null)
            ->once()
            ->andReturn($this->adapterQueryMock);

        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->createQuery()->run($this->connectionMock);
    }

    public function testCreateSelectionComplex()
    {
        $this->connectionMock->shouldReceive("getAdapter")
            ->once()
            ->with("FooAdapter")
            ->andReturn($this->fooAdapterMock);

        $this->connectionMock->shouldReceive("getMapper")
            ->once()
            ->andReturn(new UniMapper\Mapper);

        $this->fooAdapterMock->shouldReceive("createSelect")
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

        $this->fooAdapterMock->shouldReceive("onExecute")
            ->with($this->adapterQueryMock)
            ->once();

        $this->adapterQueryMock->shouldReceive("setFilter")
            ->with(["text_unmapped" => [\UniMapper\Entity\Filter::EQUAL => "foo"]])
            ->once();
        $this->adapterQueryMock->shouldReceive("setAssociations")
            ->with(
                Mockery::on(function($arg) {
                    return $arg["adapterAssociation"] instanceof Assoc;
                })
            )
            ->once();

        $this->createQuery()
            ->associate("adapterAssociation")
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
 * @property int    $id                 m:primary m:map-by(fooId)
 * @property string $text               m:map-by(text_unmapped)
 * @property Foo[]  $adapterAssociation m:assoc(type)
 * @property Bar[]  $manyToMany         m:assoc(M:N) m:assoc-by(foo_fooId|foo_bar|bar_barId)
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