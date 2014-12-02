<?php

use Tester\Assert,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class QueryTest extends UniMapper\Tests\TestCase
{

    /**
     * @throws UniMapper\Exception\QueryException Adapter 'FooAdapter' not given!
     */
    public function testAdapterRequired()
    {
        new \UniMapper\Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple"),
            [],
            new \UniMapper\Mapper
        );
    }

    /**
     * @throws UniMapper\Exception\QueryException Entity 'UniMapper\Tests\Fixtures\Entity\NoAdapter' has no adapter defined!
     */
    public function testNoAdapterEntity()
    {
        new \UniMapper\Query\Select(
            new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoAdapter"),
            [],
            new \UniMapper\Mapper
        );
    }

}

$testCase = new QueryTest;
$testCase->run();