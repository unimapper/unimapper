<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Filter;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityFilterTest extends TestCase
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
                ["id" => [Filter::EQUAL => 1]],
                ["text" => [Filter::NOT => "foo"]]
            ],
            Filter::merge(
                ["id" => [Filter::EQUAL => 1]],
                ["text" => [Filter::NOT => "foo"]]
            )
        );
    }

    public function testMergeSameNameItems()
    {
        Assert::same(
            [
                [
                    "id" => [Filter::GREATER => 1],
                    "text" => [Filter::CONTAIN => "foo1"]
                ],
                [
                    "id" => [Filter::LESS => 3],
                    "text" => [Filter::CONTAIN => "foo2"]
                ]
            ],
            Filter::merge(
                [
                    "id" => [Filter::GREATER => 1],
                    "text" => [Filter::CONTAIN => "foo1"]
                ],
                [
                    "id" => [Filter::LESS => 3],
                    "text" => [Filter::CONTAIN => "foo2"]
                ]
            )
        );
    }

    public function testMergeItemToGroup()
    {
        Assert::same(
            [
                [["id" => [Filter::GREATER => 1]]],
                ["id" => [Filter::LESS => 3]]
            ],
            Filter::merge(
                [
                    ["id" => [Filter::GREATER => 1]]
                ],
                ["id" => [Filter::LESS => 3]]
            )
        );
    }

    public function testMergeEmptyToEmpty()
    {
        Assert::same([], Filter::merge([], []));
    }

    public function testMergeToEmpty()
    {
        Assert::same(
            ["id" => [Filter::EQUAL => 1]],
            Filter::merge(
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
                ["text" => [Filter::EQUAL => "foo"]]
            ],
            Filter::merge(
                [],
                [
                    ["id" => [Filter::EQUAL => 1]],
                    ["text" => [Filter::EQUAL => "foo"]]
                ]
            )
        );
    }

    public function testMergeGroupToGroupWithOr()
    {
        Assert::same(
            [
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ],
                [["text" => [Filter::EQUAL => "foo"]]]
            ],
            Filter::merge(
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ],
                [["text" => [Filter::EQUAL => "foo"]]]
            )
        );
    }

    public function testMergeGroupWithOrToGroup()
    {
        Assert::same(
            [
                [["text" => [Filter::EQUAL => "foo"]]],
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ]
            ],
            Filter::merge(
                [
                    ["text" => [Filter::EQUAL => "foo"]]
                ],
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ]
            )
        );
    }

    public function testMergeGroupWithOrToGroupWithOr()
    {
        Assert::same(
            [
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ],
                [
                    Filter::_OR => [
                        "text" => [Filter::EQUAL => "foo"]
                    ]
                ]
            ],
            Filter::merge(
                [
                    Filter::_OR => [
                        "id" => [Filter::EQUAL => 1]
                    ]
                ],
                [
                    Filter::_OR => [
                        "text" => [Filter::EQUAL => "foo"]
                    ]
                ]
            )
        );
    }

    public function testMergeTwoItemsToEmpty()
    {
        $filter = [
            "id" => [
                Filter::EQUAL => 1,
                Filter::NOT => 2
            ],
            "text" => [Filter::EQUAL => ["foo"]]
        ];
        Assert::same($filter, Filter::merge([], $filter));
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter group structure!
     */
    public function testValidateWithEmptyGroup()
    {
        Filter::validate(Reflection::load("Entity"), [[]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testValidateWithInvalidGroup()
    {
        Filter::validate(Reflection::load("Entity"), ["foo" => "foo"]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testValidateInvalidFilterWithMixedGroupAndItems()
    {
        Filter::validate(
            Reflection::load("Entity"),
            [
                "id" => [Filter::EQUAL => 1],
                ["text" => [Filter::EQUAL => "foo"]]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testValidateInvalidFilterWithOrAndGroup()
    {
        Filter::validate(
            Reflection::load("Entity"),
            [
                ["text" => [Filter::EQUAL => "foo"]],
                Filter::_OR => [Filter::EQUAL => 1]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter structure!
     */
    public function testValidateInvalidFilterGroupWithMixedOrAndItems()
    {
        Filter::validate(
            Reflection::load("Entity"),
            [
                ["id" => [Filter::EQUAL => 1]],
                Filter::_OR => [
                    "text" => [Filter::EQUAL => "foo"]
                ],
                "text" => [Filter::EQUAL => "foo"]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Invalid filter group structure!
     */
    public function testValidateWithNoModifier()
    {
        Filter::validate(Reflection::load("Entity"), ["foo"]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Undefined property name 'undefinedProperty' used in filter!
     */
    public function testValidateWithUndefinedProperty()
    {
        Filter::validate(Reflection::load("Entity"), ["undefinedProperty" => [Filter::EQUAL => 1]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Undefined property name '%or' used in filter!
     */
    public function testValidateUndefinedPropertyWithNameIdenticalToOrModifier()
    {
        Filter::validate(
            Reflection::load("Entity"),
            [
                Filter::_OR => [Filter::EQUAL => 1],
                ["text" => [Filter::EQUAL => "foo"]]
            ]
        );
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with computed, collections, entities and disabled mapping!
     */
    public function testValidateNotAllowedOnComputed()
    {
        Filter::validate(Reflection::load("Entity"), ["computed" => [Filter::EQUAL => new DateTime]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with computed, collections, entities and disabled mapping!
     */
    public function testValidateNotAllowedOnEntityType()
    {
        Filter::validate(Reflection::load("Entity"), ["entity" => [Filter::EQUAL => 1]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with computed, collections, entities and disabled mapping!
     */
    public function testValidateNotAllowedOnCollectionType()
    {
        Filter::validate(Reflection::load("Entity"), ["collection" => [Filter::EQUAL => 1]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Filter can not be used with computed, collections, entities and disabled mapping!
     */
    public function testValidateMappingNotAllowedOnDsiabled()
    {
        Filter::validate(Reflection::load("Entity"), ["disabledMap" => [Filter::EQUAL => 1]]);
    }

    public function testValidateArray()
    {
        Filter::validate(Reflection::load("Entity"), ["id" => [Filter::EQUAL => [1]]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Expected integer but string given on property id!
     */
    public function testValidateInvalidValueType()
    {
        Filter::validate(Reflection::load("Entity"), ["id" => [Filter::EQUAL => "foo"]]);
    }

    /**
     * @throws UniMapper\Exception\FilterException Expected integer but string given on property id!
     */
    public function testValidateInvalidValueTypeInArray()
    {
        Filter::validate(Reflection::load("Entity"), ["id" => [Filter::EQUAL => ["foo"]]]);
    }

}

/**
 * @adapter Foo
 *
 * @property int      $id          m:primary
 * @property string   $text
 * @property int      $computed    m:computed
 * @property int      $disabledMap m:map(false)
 * @property Entity   $entity
 * @property Entity[] $collection
 */
class Entity extends \UniMapper\Entity
{
    public function computeComputed()
    {}
}

$testCase = new EntityFilterTest;
$testCase->run();