<?php

use Tester\Assert;
use UniMapper\Tests\Fixtures;
use UniMapper\NamingConvention as UNC;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class RepositoryTest extends \Tester\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockery\Mock $adapterMock */
    private $adapterMock;

    public function setUp()
    {
        $this->adapterMock = Mockery::mock("UniMapper\Adapter");
        $this->repository = $this->createRepository("Simple", ["FooAdapter" => $this->adapterMock]);
    }

    private function createRepository($name, array $adapters = [])
    {
        $connection = new \UniMapper\Connection(new \UniMapper\Mapper);
        foreach ($adapters as $adapterName => $adapter) {
            $connection->registerAdapter($adapterName, $adapter);
        }

        $class = UNC::nameToClass($name, UNC::REPOSITORY_MASK);
        return new $class($connection);
    }

    public function testGetName()
    {
        Assert::same("Simple", $this->repository->getName());
    }

    public function testGetEntityName()
    {
        Assert::same("Simple", $this->repository->getEntityName());
    }

    public function testSaveUpdate()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("simple_resource", "simplePrimaryId", 2, ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(true);

        $entity = new Fixtures\Entity\Simple(["id" => 2, "text" => "foo"]);
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

        $this->repository->save($entity);

        Assert::same(2, $entity->id);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Entity was not successfully updated!
     */
    public function testSaveUpdateFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createUpdateOne")
            ->once()
            ->with("simple_resource", "simplePrimaryId", 2, ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(false);

        $entity = new Fixtures\Entity\Simple(["id" => 2, "text" => "foo"]);
        $this->repository->save($entity);
    }

    public function testSaveInsert()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createInsert")
            ->once()
            ->with("simple_resource", ["text" => "foo"])
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->once()
            ->with($adapterQueryMock)
            ->andReturn(["id" => 1]);

        $entity = new Fixtures\Entity\Simple(["simplePrimaryId" => null, "text" => "foo"]);
        $entity->manyToMany[] = new Fixtures\Entity\Remote(new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Remote")); // Associations are ignored

        $this->repository->save($entity);

        Assert::same(1, $entity->id);
    }

    public function testSaveInvalid()
    {
        $entity = new Fixtures\Entity\Simple(["email" => "invalidemail", "text" => "foo"]);
        $entity->getValidator()->on("email")->addRule(\UniMapper\Validator::EMAIL, "Invalid e-mail format!");

        try {
            $this->repository->save($entity);
        } catch (UniMapper\Exception\ValidatorException $e) {
            $validator = $e->getValidator();
        }

        Assert::isEqual(
            [
                'properties' => [
                    'email' => [
                        new \UniMapper\Validator\Message('Invalid e-mail format!', 1)
                    ]
                ]
            ],
            (array) $validator->getMessages()
        );
    }

    /**
     * @throws UniMapper\Exception\QueryException Primary value can not be empty!
     */
    public function testDeleteNoPrimaryValue()
    {
        $this->repository->destroy(new Fixtures\Entity\Simple);
    }

    public function testDelete()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(true);

        $entity = new Fixtures\Entity\Simple(["id" => 1]);
        $this->repository->destroy($entity);
    }

    public function testDeleteFailed()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createDeleteOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(false);

        $entity = new Fixtures\Entity\Simple(["id" => 1]);
        Assert::false($this->repository->destroy($entity));
    }

    public function testFindOne()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(["simplePrimaryId" => 1]);

        $result = $this->repository->findOne(1);

        Assert::type("UniMapper\Entity", $result);
        Assert::same(1, $result->id);
    }

    public function testFindOneNotFound()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");

        $this->adapterMock->shouldReceive("createSelectOne")
            ->with("simple_resource", "simplePrimaryId", 1)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn(null);

        Assert::false($this->repository->findOne(1));
    }

    public function testFindPrimaries()
    {
        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->once()
            ->with([["simplePrimaryId", "IN", [1, 2], "AND"]]);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createSelect")
            ->with("simple_resource", ['simplePrimaryId', 'text', 'empty', 'link', 'email_address', 'time', 'ip', 'mark', 'entity', 'readonly', 'stored_data', 'enumeration'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once()
            ->andReturn([["simplePrimaryId" => 1], ["simplePrimaryId" => 2]]);

        $result = $this->repository->findPrimaries([1, 2]);

        Assert::type("UniMapper\EntityCollection", $result);
        Assert::same(1, $result[0]->id);
        Assert::same(2, $result[1]->id);
    }

    public function testFindWithFilter()
    {
        $filter = [
            "one" => [
                "!" => 1
            ],
            "two" => [
                "!" => null, // IS NOT NULL
            ],
            "three" => [
                "!" => [2] // NOT IN
            ],
            "four" => [
                "=" => 3
            ],
            "five" => [
                "=" => null, // IS NULL
            ],
            "six" => [
                "=" => [4], // IN
                "<=" => 5,
                ">=" => 6,
                ">" => 7,
                "<" => 8
            ],
            "text" => [
                "like" => "%foo%"
            ],
            "bool" => [
                "!" => false, // IS NOT TRUE
                "=" => true // IS TRUE
            ]
        ];
        $expectedFilter = [
            ["one", "!=", 1, "AND"],
            ["two", "IS NOT", null, "AND"],
            ["three", "NOT IN", [2], "AND"],
            ["four", "=", 3, "AND"],
            ["five", "IS", null, "AND"],
            ["six", "IN", [4], "AND"],
            ["six", "<=", 5, "AND"],
            ["six", ">=", 6, "AND"],
            ["six", ">", 7, "AND"],
            ["six", "<", 8, "AND"],
            ["text", "LIKE", "%foo%", "AND"],
            ["bool", "IS NOT", false, "AND"],
            ["bool", "IS", true, "AND"]
        ];

        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->once()
            ->with($expectedFilter);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createSelect")
            ->with("filter_resource", ['one', 'two', 'three', 'four', 'five', 'six', 'bool', 'text'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once();

        $this->createRepository("Filter", ["FooAdapter" => $this->adapterMock])->find($filter);
    }

    public function testFindWithDeepFilter()
    {
        // WHERE (one = 1 OR two = 2) OR ((three = 3 OR four = 4) AND (five > 4 AMD five < 6 AND six = 6))
        $filter = [
            "or" => [
                [
                    "or" => [
                        "one" => ["=" => 1],
                        "two" => ["=" => 2]
                    ]
                ],
                [
                    [
                        "or" => [
                            "three" => ["=" => 3],
                            "four" => ["=" => 4]
                        ]
                    ],
                    [
                        "five" => [
                            ">" => 4,
                            "<" => 6
                        ],
                        "six" => ["=" => 6]
                    ]
                ]
            ]
        ];
        $expectedFilter = [
            [
                [
                    [
                        [
                            [
                                [
                                    ['one', '=', 1, 'OR'],
                                    ['two', '=', 2, 'OR']
                                ],
                                'AND'
                            ]
                        ],
                        'OR'
                    ],
                    [
                        [
                            [
                                [
                                    [
                                        [
                                            ['three', '=', 3, 'OR'],
                                            ['four', '=', 4, 'OR']
                                        ],
                                        'AND'
                                    ]
                                ],
                                'AND'
                            ],
                            [
                                [
                                    ['five', '>', 4, 'AND'],
                                    ['five', '<', 6, 'AND'],
                                    ['six', '=', 6, 'AND']
                                ],
                                'AND'
                            ]
                        ],
                        'OR'
                    ]
                ],
                'AND'
            ]
        ];

        $adapterQueryMock = Mockery::mock("UniMapper\Adapter\IQuery");
        $adapterQueryMock->shouldReceive("setConditions")
            ->once()
            ->with($expectedFilter);
        $adapterQueryMock->shouldReceive("getRaw")->once();

        $this->adapterMock->shouldReceive("createSelect")
            ->with("filter_resource", ['one', 'two', 'three', 'four', 'five', 'six', 'bool', 'text'], [], null, null)
            ->once()
            ->andReturn($adapterQueryMock);
        $this->adapterMock->shouldReceive("onExecute")
            ->with($adapterQueryMock)
            ->once();

        $this->createRepository("Filter", ["FooAdapter" => $this->adapterMock])->find($filter);
    }

    /**
     * @throws UniMapper\Exception\QueryException Condition group must contain one condition at least!
     */
    public function testFindWithInvalidFilter()
    {
        $this->createRepository("Filter", ["FooAdapter" => $this->adapterMock])->find([[]]);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Invalid property name 'undefinedProperty'!
     */
    public function testFindWithInvalidFilterUndefinedProperty()
    {
        $this->createRepository("Filter", ["FooAdapter" => $this->adapterMock])->find([["undefinedProperty" => ["=" => 1]]]);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Values can not be empty!
     */
    public function testFindPrimaryNoValues()
    {
        $this->repository->findPrimaries([]);
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Method can not be used because entity NoPrimary has no primary property defined!
     */
    public function testFindPrimaryWithNoPrimaryEntity()
    {
        $this->createRepository("NoPrimary")->findPrimaries([]);
    }

}

$testCase = new RepositoryTest;
$testCase->run();