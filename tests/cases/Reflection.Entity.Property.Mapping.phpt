<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityPropertyMappingTest extends UniMapper\Tests\TestCase
{


    public function testNameAndFilter()
    {
        $reflection = new \UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
        Assert::same("stored_data", $reflection->getProperty("storedData")->getMapping()->getName());
        Assert::same(
            ['UniMapper\Tests\Fixtures\Entity\Simple', 'stringToArray'],
            $reflection->getProperty("storedData")->getMapping()->getFilterIn()
        );
        Assert::same(
            ['UniMapper\Tests\Fixtures\Entity\Simple', 'arrayToString'],
            $reflection->getProperty("storedData")->getMapping()->getFilterOut()
        );
    }

}

$testCase = new ReflectionEntityPropertyMappingTest;
$testCase->run();