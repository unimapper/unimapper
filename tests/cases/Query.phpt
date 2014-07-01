<?php

use Tester\Assert,
    UniMapper\Reflection,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class QueryTest extends Tester\TestCase
{

    /** @var \Mockista\Mock $mapperMock */
    private $mapperMock;

    public function setUp()
    {
        $mockista = new \Mockista\Registry;

        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");
        $this->mapperMock->expects("insert")->once()->andReturn(1);
        $this->mapperMock->freeze();
    }

    /**
     * Create conditionable query
     *
     * @return \UniMapper\Tests\Fixtures\Query\Conditionable
     */
    private function createConditionable()
    {
        return new Fixtures\Query\Conditionable(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            ["FooMapper" => $this->mapperMock]
        );
    }

    public function testConditions()
    {
        $query = $this->createConditionable();
        $expectedConditions = [];

        // where()
        $query->where("id", ">", 1);
        $expectedConditions[] = ["id", ">", 1, "AND"];
        Assert::same($expectedConditions, $query->getConditions());

        // orWhere()
        $query->orWhere("text", "=", "foo");
        $expectedConditions[] = ["text", "=", "foo", "OR"];
        Assert::same($expectedConditions, $query->getConditions());

        // whereAre()
        $query->whereAre(function($query) {
            $query->where("id", "<", 2)
                  ->orWhere("text", "LIKE", "anotherFoo");
        });
        $expectedConditions[] = [
            [
                ['id', '<', 2, 'AND'],
                ['text', 'LIKE', 'anotherFoo', 'OR']
            ],
            'AND'
        ];
        Assert::same($expectedConditions, $query->getConditions());

        // orWhereAre()
        $query->orWhereAre(function($query) {
            $query->where("id", "<", 5);
            $query->orWhere("text", "LIKE", "yetAnotherFoo");
        });
        $expectedConditions[] = [
            [
                ['id', '<', 5, 'AND'],
                ['text', 'LIKE', 'yetAnotherFoo', 'OR'],
            ],
            'OR'
        ];
        Assert::same($expectedConditions, $query->getConditions());

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
                ['id', '=', 4, 'AND'],
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
        Assert::same($expectedConditions, $query->getConditions());
    }

    /**
     * @throws UniMapper\Exceptions\QueryException Mapper 'FooMapper' not given!
     */
    public function testMapperRequired()
    {
        new \UniMapper\Query\FindAll(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            []
        );
    }

}

$testCase = new QueryTest;
$testCase->run();