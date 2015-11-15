<?php

use Tester\Assert;
use UniMapper\Query;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectableTest extends TestCase
{

    public function testAssociate()
    {
        Assert::same(
            [
                "local" => [
                    "assoc" => Foo::getReflection()
                        ->getProperty("assoc")
                        ->getOption(Reflection\Property\Option\Assoc::KEY)
                ],
                "remote" => [
                    "assocRemote" => Foo::getReflection()
                        ->getProperty("assocRemote")
                        ->getOption(Reflection\Property\Option\Assoc::KEY)
                ]
            ],
            $this->createQuery()
                ->associate("assoc", "assocRemote")
                ->associations
        );
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'undefined' is not defined on entity Foo!
     */
    public function testAssociateUndefinedProperty()
    {
        $this->createQuery()->associate("undefined");
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'id' is not defined as association on entity Foo!
     */
    public function testAssociateNonAssociation()
    {
        $this->createQuery()->associate("id");
    }

    public function testSelect()
    {
        Assert::same(["id"], $this->createQuery()->select("id")->selection);
        Assert::same(["id", "foo"], $this->createQuery()->select(["id", "foo"])->selection);
        Assert::same(["id", "foo"], $this->createQuery()->select("id", "foo")->selection);
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'undefined' is not defined on entity Foo!
     */
    public function testSelectUndefinedProperty()
    {
        $this->createQuery()->select("undefined");
    }

    /**
     * @throws UniMapper\Exception\QueryException Associations, computed and properties with disabled mapping can not be selected!
     */
    public function testSelectDisabledMapping()
    {
        $this->createQuery()->select("disabledMap");
    }

    private function createQuery()
    {
        return Foo::query()->select();
    }

}

/**
 * @adapter FooAdapter(fooResource)
 *
 * @property int    $id          m:primary
 * @property string $foo
 * @property Foo[]  $assoc       m:assoc(1:1) m:assoc-by(key)
 * @property Bar[]  $assocRemote m:assoc(1:1) m:assoc-by(key)
 * @property string $disabledMap m:map(false)
 */
class Foo extends \UniMapper\Entity {}

/**
 * @adapter BarAdapter(barResource)
 *
 * @property int $id m:primary m:map-by(barId)
 */
class Bar extends \UniMapper\Entity {}

$testCase = new QuerySelectableTest;
$testCase->run();