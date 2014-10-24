<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryTest extends UniMapper\Tests\TestCase
{

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Tests\Fixtures\Adapter\Simple");
    }

    /**
     * Create conditionable query
     *
     * @return \UniMapper\Tests\Fixtures\Query\Conditionable
     */
    private function createConditionableQuery()
    {
        return new UniMapper\Query\Find(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooAdapter" => $this->adapterMock]
        );
    }

    public function testConditions()
    {
        $this->adapterMock->shouldReceive("insert")->once()->andReturn(1);

        $query = $this->createConditionableQuery();
        $expectedConditions = [];

        // where()
        $query->where("id", ">", 1);
        $expectedConditions[] = ["simplePrimaryId", ">", 1, "AND"];
        Assert::same($expectedConditions, $query->conditions);

        // orWhere()
        $query->orWhere("text", "=", "foo");
        $expectedConditions[] = ["text", "=", "foo", "OR"];
        Assert::same($expectedConditions, $query->conditions);

        // whereAre()
        $query->whereAre(function($query) {
            $query->where("id", "<", 2)
                  ->orWhere("text", "LIKE", "anotherFoo");
        });
        $expectedConditions[] = [
            [
                ['simplePrimaryId', '<', 2, 'AND'],
                ['text', 'LIKE', 'anotherFoo', 'OR']
            ],
            'AND'
        ];
        Assert::same($expectedConditions, $query->conditions);

        // orWhereAre()
        $query->orWhereAre(function($query) {
            $query->where("id", "<", 5);
            $query->orWhere("text", "LIKE", "yetAnotherFoo");
        });
        $expectedConditions[] = [
            [
                ['simplePrimaryId', '<', 5, 'AND'],
                ['text', 'LIKE', 'yetAnotherFoo', 'OR'],
            ],
            'OR'
        ];
        Assert::same($expectedConditions, $query->conditions);

        // Deep nesting
        $query->whereAre(function($query) {
            $query->where("id", "=", 4);
            $query->orWhereAre(function($query) {
                $query->where("text", "LIKE", "yetAnotherFoo2");
                $query->whereAre(function($query) {
                    $query->orWhere("text", "LIKE", "yetAnotherFoo3");
                    $query->orWhere("text", "LIKE", "yetAnotherFoo4");
                });
                $query->orWhere("text", "LIKE", "yetAnotherFoo5");
            });
        });
        $expectedConditions[] = [
            [
                ['simplePrimaryId', '=', 4, 'AND'],
                [
                    [
                        ['text', 'LIKE', 'yetAnotherFoo2', 'AND'],
                        [
                            [
                                ['text', 'LIKE', 'yetAnotherFoo3', 'OR'],
                                ['text', 'LIKE', 'yetAnotherFoo4', 'OR'],
                            ],
                            'AND'
                        ],
                        ['text', 'LIKE', 'yetAnotherFoo5', 'OR']
                    ],
                    'OR'
                ]
            ],
            'AND'
        ];
        Assert::same($expectedConditions, $query->conditions);
    }

    /**
     * @throws UniMapper\Exception\QueryException Adapter 'FooAdapter' not given!
     */
    public function testAdapterRequired()
    {
        new \UniMapper\Query\Find(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            []
        );
    }

    /**
     * @throws UniMapper\Exception\QueryException Entity 'UniMapper\Tests\Fixtures\Entity\NoAdapter' has no adapter defined!
     */
    public function testNoAdapterEntity()
    {
        new \UniMapper\Query\Find(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoAdapter"),
            []
        );
    }

}

$testCase = new QueryTest;
$testCase->run();