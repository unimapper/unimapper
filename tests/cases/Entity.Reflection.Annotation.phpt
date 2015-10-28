<?php

use Tester\Assert;
use UniMapper\Entity\Reflection;

require __DIR__ . '/../bootstrap.php';

class Option implements Reflection\Property\IOption
{

    public static function getKey()
    {
        return "option";
    }

    public static function create(
        \UniMapper\Entity\Reflection\Property $property,
        $value = null,
        array $parameters = []
    ) {

    }

}

/** @param int $id m:option */
class Entity extends \UniMapper\Entity {}

/**
 * @testCase
 */
class EntityReflectionAnnotationTest extends \Tester\TestCase
{

    public function testParseAdapter()
    {
        Assert::same(
            array('Foo', 'bar_'),
            Reflection\Annotation::parseAdapter(" *  @adapter  Foo( bar_ ) ")
        );
        Assert::same(
            array('Foo', ''),
            Reflection\Annotation::parseAdapter(" *  @adapter  Foo() ")
        );
        Assert::same(
            array('Foo', ''),
            Reflection\Annotation::parseAdapter(" *  @adapter  Foo ")
        );
        Assert::same(array('Foo', ''), Reflection\Annotation::parseAdapter(" *  @adapter  Foo( bar _ ) "));
    }

    public function testParseOptions()
    {
        Assert::same(
            array(
                'assoc' => '',
                'assoc-filter-by' => 'value1 | value2',
                'primary' => NULL,
                'map-by' => '',
                'map' => 'false',
            ),
            Reflection\Annotation::parseOptions("m:assoc( ) m:assoc-filter-by( value1 | value2 ) m:primary m:map-by() m:map(false)")
        );
    }

    public function testRegisterOption()
    {
        Reflection\Annotation::registerOption("custom", "Option");
    }

    /**
     * @throws UniMapper\Exception\AnnotationException Option key can not be empty!
     */
    public function testRegisterOptionEmptyKey()
    {
        Reflection\Annotation::registerOption("", "");
    }

    /**
     * @throws UniMapper\Exception\AnnotationException Class stdClass should implement UniMapper\Entity\Reflection\Property\IOption!
     */
    public function testRegisterOptionInterfaceNotImplemented()
    {
        Reflection\Annotation::registerOption("custom", "stdClass");
    }

    /**
     * @throws UniMapper\Exception\AnnotationException Class Undefined not found!
     */
    public function testRegisterOptionUndefinedClass()
    {
        Reflection\Annotation::registerOption("custom", "Undefined");
    }

    public static function getRegisteredOptions()
    {
        Assert::same(
            [
                Assoc::KEY => 'UniMapper\Entity\Reflection\Property\Option\Assoc',
                Computed::KEY => 'UniMapper\Entity\Reflection\Property\Option\Computed',
                Enum::KEY => 'UniMapper\Entity\Reflection\Property\Option\Enum',
                Map::KEY => 'UniMapper\Entity\Reflection\Property\Option\Map',
                Primary::KEY => 'UniMapper\Entity\Reflection\Property\Option\Primary'
            ],
            Reflection\Annotation::getRegisteredOptions()
        );
    }

    public function testParseProperties()
    {
        $matched = Reflection\Annotation::parseProperties(
            '*  @property  type  $one  m:filter Commentary
            * @property-read type $two m:filter m:filter  Comment
            *@property type $three
            *@property
            @property  not parsed
            *@property  type $four Comment'
        );

        Assert::same("", $matched[0][1]);
        Assert::same("type", $matched[0][2]);
        Assert::same("one", $matched[0][3]);
        Assert::same("  m:filter Commentary", $matched[0][4]);

        Assert::same("-read", $matched[1][1]);
        Assert::same("type", $matched[1][2]);
        Assert::same("two", $matched[1][3]);
        Assert::same(" m:filter m:filter  Comment", $matched[1][4]);

        Assert::same("", $matched[2][1]);
        Assert::same('type', $matched[2][2]);
        Assert::same("three", $matched[2][3]);
        Assert::same("", $matched[2][4]);

        Assert::same("", $matched[3][1]);
        Assert::same('type', $matched[3][2]);
        Assert::same("four", $matched[3][3]);
        Assert::same(" Comment", $matched[3][4]);

        Assert::count(4, $matched);
    }

}

$testCase = new EntityReflectionAnnotationTest;
$testCase->run();