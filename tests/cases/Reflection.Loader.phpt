<?php

use Tester\Assert;
use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ReflectionLoaderTest extends UniMapper\Tests\TestCase
{

    public function testGetRelatedFiles()
    {
        Assert::same(
            array(
                Reflection\Loader::load("Simple")->getFileName(),
                Reflection\Loader::load("Nested")->getFileName(),
                Reflection\Loader::load("Remote")->getFileName()
            ),
           Reflection\Loader::getRelatedFiles("Simple")
        );
    }

}

$testCase = new ReflectionLoaderTest;
$testCase->run();