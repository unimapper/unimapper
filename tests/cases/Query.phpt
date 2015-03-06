<?php

use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryTest extends \Tester\TestCase
{

    /**
     * @throws UniMapper\Exception\QueryException Can not create query because entity UniMapper\Tests\Fixtures\Entity\NoAdapter has no adapter defined!
     */
    public function testNoAdapterEntity()
    {
        new UniMapper\Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoAdapter")
        );
    }

}

$testCase = new QueryTest;
$testCase->run();