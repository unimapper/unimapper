<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryFilterableTest extends \Tester\TestCase
{

    /**
     * @return UniMapper\Query\Select
     */
    private function createFilterableQuery()
    {
        return new UniMapper\Query\Select(Reflection::load("Simple"));
    }

    public function testSetFilter()
    {
        $filter = ["id" => [\UniMapper\Entity\Filter::EQUAL => 1]];
        Assert::same($filter, $this->createFilterableQuery()->setFilter($filter)->filter);
    }

}

$testCase = new QueryFilterableTest;
$testCase->run();