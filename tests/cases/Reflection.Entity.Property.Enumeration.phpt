<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityPropertyEnumerationTest extends UniMapper\Tests\TestCase
{

    public function testSelf()
    {
        preg_match(\UniMapper\Reflection\Entity\Property\Enumeration::EXPRESSION, "m:enum(self::ENUMERATION_*)", $matches);
        $enum = new \UniMapper\Reflection\Entity\Property\Enumeration($matches, "UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::same(['ENUMERATION_ONE' => 1, 'ENUMERATION_TWO' => 2], $enum->getValues());
    }

    public function testClass()
    {
        preg_match(\UniMapper\Reflection\Entity\Property\Enumeration::EXPRESSION, "m:enum(UniMapper\Tests\Fixtures\Entity\Simple::ENUMERATION_*)", $matches);
        $enum = new \UniMapper\Reflection\Entity\Property\Enumeration($matches, "UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::same(['ENUMERATION_ONE' => 1, 'ENUMERATION_TWO' => 2], $enum->getValues());
    }

}

$testCase = new ReflectionEntityPropertyEnumerationTest;
$testCase->run();