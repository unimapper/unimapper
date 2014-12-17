<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ReflectionAnnotationParserTest extends UniMapper\Tests\TestCase
{

    public function testParseAdapter()
    {
        Assert::same(
            array('Foo', 'bar_'),
            Reflection\AnnotationParser::parseAdapter(" *  @adapter  Foo( bar_ ) ")
        );
        Assert::same(
            array('Foo', ''),
            Reflection\AnnotationParser::parseAdapter(" *  @adapter  Foo() ")
        );
        Assert::same(
            array('Foo', ''),
            Reflection\AnnotationParser::parseAdapter(" *  @adapter  Foo ")
        );
        Assert::same(array('Foo', ''), Reflection\AnnotationParser::parseAdapter(" *  @adapter  Foo( bar _ ) "));
    }

    public function testParseOptions()
    {
        Assert::same(
            array(
                'assoc-filter-by' => 'value1|value2',
                'primary' => null,
            ),
            Reflection\AnnotationParser::parseOptions(" m:assoc-filter-by(value1|value2) m:primary ")
        );
    }

    public function testParseProperties()
    {
        $matched = Reflection\AnnotationParser::parseProperties(
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

$testCase = new ReflectionAnnotationParserTest;
$testCase->run();