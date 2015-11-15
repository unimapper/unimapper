<?php

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class QueryTest extends TestCase
{

    /**
     * @throws UniMapper\Exception\QueryException Can not create query because entity NoAdapter has no adapter defined!
     */
    public function testNoAdapterEntity()
    {
        new UniMapper\Query\Select(\UniMapper\Entity\Reflection::load("NoAdapter"));
    }

}

class NoAdapter extends \UniMapper\Entity {}

$testCase = new QueryTest;
$testCase->run();