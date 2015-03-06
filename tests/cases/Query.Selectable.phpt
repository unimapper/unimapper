<?php

use Tester\Assert;
use UniMapper\Query;
use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QuerySelectableTest extends \Tester\TestCase
{

    public function testAssociate()
    {
        Assert::same(["manyToMany"], array_keys($this->createQuery()->associate("manyToMany")->associations["remote"]));
        Assert::same(["manyToMany", "manyToOne"], array_keys($this->createQuery()->associate(["manyToMany", "manyToOne"])->associations["remote"]));
        Assert::same(["manyToMany", "manyToOne"], array_keys($this->createQuery()->associate("manyToMany", "manyToOne")->associations["remote"]));
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'undefined' is not defined on entity UniMapper\Tests\Fixtures\Entity\Simple!
     */
    public function testAssociateUndefinedProperty()
    {
        $this->createQuery()->associate("undefined");
    }

    /**
     * @throws UniMapper\Exception\QueryException Property 'id' is not defined as association on entity UniMapper\Tests\Fixtures\Entity\Simple!
     */
    public function testAssociateNonAssociation()
    {
        $this->createQuery()->associate("id");
    }

    private function createQuery($entity = "Simple")
    {
        return new Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\\" . $entity)
        );
    }

}

$testCase = new QuerySelectableTest;
$testCase->run();
