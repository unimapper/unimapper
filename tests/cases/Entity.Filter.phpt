<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Filter;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityFilterTest extends \Tester\TestCase
{

    public function testIsGroup()
    {
        Assert::true(Filter::isGroup([]));
        Assert::true(Filter::isGroup(["id"]));
        Assert::true(Filter::isGroup([[]]));
        Assert::false(Filter::isGroup(["id" => []]));
    }

    public function testMerge()
    {
        Assert::same(
            [
                "id" => [Filter::EQUAL => 1],
                "text" => [Filter::NOT => "foo"]
            ],
            Filter::merge(
                Reflection::load("Simple"),
                ["id" => [Filter::EQUAL => 1]],
                ["text" => [Filter::NOT => "foo"]]
            )
        );
    }

    public function testMergeEmptyToEmpty()
    {
        Assert::same([], Filter::merge(Reflection::load("Simple"), [], []));
    }

    public function testMergeToEmpty()
    {
        Assert::same(
            ["id" => [Filter::EQUAL => 1]],
            Filter::merge(
                Reflection::load("Simple"),
                [],
                ["id" => [Filter::EQUAL => 1]]
            )
        );
    }

    public function testMergeGroupToEmpty()
    {
        Assert::same(
            [
                ["id" => [Filter::EQUAL => 1]],
                ["text" => [Filter::LIKE => "foo"]]
            ],
            Filter::merge(
                Reflection::load("Simple"),
                [],
                [
                    ["id" => [Filter::EQUAL => 1]],
                    ["text" => [Filter::LIKE => "foo"]]
                ]
            )
        );
    }

    public function testMergeGroupToGroupWithOr()
    {
        Assert::same(
            [
                Filter::_OR => [
                    "id" => [Filter::EQUAL => 1]
                ],
                [
                    "text" => [Filter::LIKE => "foo"]
                ]
            ],
            Filter::merge(
                Reflection::load("Simple"),
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ],
                ],
                [
                    [
                        "text" => [Filter::LIKE => "foo"]
                    ]
                ]
            )
        );
    }

    public function testMergeGroupWithOrToGroup()
    {
        Assert::same(
            [
                [
                    "text" => [Filter::LIKE => "foo"]
                ],
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ]
            ],
            Filter::merge(
                Reflection::load("Simple"),
                [
                    [
                        "text" => [Filter::LIKE => "foo"]
                    ]
                ],
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ],
                ]
            )
        );
    }

    public function testMergeGroupWithOrToGroupWithOr()
    {
        Assert::same(
            [
                Filter::_OR => [
                    "id" => [Filter::EQUAL => 1]
                ],
                [
                    Filter::_OR => [
                        "text" => [Filter::LIKE => "foo"]
                    ]
                ]
            ],
            Filter::merge(
                Reflection::load("Simple"),
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ],
                [
                    Filter::_OR => [
                        "text" => [Filter::LIKE => "foo"]
                    ]
                ]
            )
        );
    }

    public function testMergeToEmptySingleItem()
    {
        $filter = ["id" => [Filter::EQUAL => 1]];
        Assert::same($filter, Filter::merge(Reflection::load("Simple"), [], $filter));
    }

    public function testMergeToEmptyWithTwoItems()
    {
        $filter = [
            "id" => [
                Filter::EQUAL => 1,
                Filter::NOT => 2
            ],
            "text" => [
                Filter::EQUAL => ["foo"]
            ]
        ];
        Assert::same($filter, Filter::merge(Reflection::load("Simple"), [], $filter));
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter group structure!
     */
    public function testMergeWithEmptyGroup()
    {
        Filter::merge(Reflection::load("Simple"), [], [[]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testMergeWithInvalidGroup()
    {
        Filter::merge(Reflection::load("Simple"), [], ["foo" => "foo"]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testMergeInvalidFilterWithMixedGroupAndItems()
    {
        Filter::merge(
            Reflection::load("Simple"),
            [],
            [
                "id" => [Filter::EQUAL => 1],
                ["text" => [Filter::EQUAL => "foo"]]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter group structure!
     */
    public function testMergeInvalidFilterGroupWithMixedOrAndItems()
    {
        Filter::merge(
            Reflection::load("Simple"),
            [],
            [
                ["id" => [Filter::EQUAL => 1]],
                Filter::_OR => [
                    "text" => [Filter::EQUAL => "foo"]
                ],
                "text" => [Filter::LIKE => "foo"]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter group structure!
     */
    public function testMergeWithNoModifier()
    {
        Filter::merge(Reflection::load("Simple"), [], ["foo"]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Undefined property name 'undefinedProperty' used in filter!
     */
    public function testMergeWithUndefinedProperty()
    {
        Filter::merge(Reflection::load("Simple"), [], ["undefinedProperty" => [Filter::EQUAL => 1]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with associations, computed, collections and entities!
     */
    public function testMergeWithNotAllowedComputed()
    {
        Filter::merge(Reflection::load("Simple"), [], ["year" => [Filter::EQUAL => new DateTime]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with associations, computed, collections and entities!
     */
    public function testMergeWithNotAllowedAssociation()
    {
        Filter::merge(Reflection::load("Simple"), [], ["collection" => [Filter::EQUAL => 1]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with associations, computed, collections and entities!
     */
    public function testMergeWithNotAllowedCollection()
    {
        Filter::merge(Reflection::load("Simple"), [], ["entity" => [Filter::EQUAL => 1]]);
    }

}

$testCase = new EntityFilterTest;
$testCase->run();