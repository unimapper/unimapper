<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Filter;

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
        $filter = ["id" => [Filter::EQUAL => 1]];
        Assert::same($filter, $this->createFilterableQuery()->setFilter($filter)->filter);
    }

    public function testWhere()
    {
        Assert::same(
            [
                ['id' => [Filter::EQUAL => 1]],
                ['text' => [Filter::EQUAL => 'foo']]
            ],
            $this->createFilterableQuery()
                ->where(["id" => [Filter::EQUAL => 1]])
                ->where(["text" => [Filter::EQUAL => "foo"]])
                ->filter
        );
    }

    public function testWhereGroupAndItem()
    {
        Assert::same(
            [
                ['id' => [Filter::EQUAL => 1]],
                [
                    Filter::_OR => [
                        ['text' => [Filter::CONTAIN => 'foo']],
                        ['text' => [Filter::CONTAIN => 'foo2']]
                    ]
                ]
            ],
            $this->createFilterableQuery()
                ->where(["id" => [Filter::EQUAL => 1]])
                ->where(
                    [
                        Filter::_OR => [
                            ['text' => [Filter::CONTAIN => 'foo']],
                            ['text' => [Filter::CONTAIN => 'foo2']]
                        ]
                    ]
                )
                ->filter
        );
    }

}

$testCase = new QueryFilterableTest;
$testCase->run();