<?php

use UniMapper\Association;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AssociationEntities.php';

/**
 * @testCase
 */
class AssociationTest extends TestCase
{

    /**
     * @throws UniMapper\Exception\AssociationException Associations with same adapters should be managed by relevant adapter!
     */
    public function testConstructSameAdapters()
    {
        new Association(Bar::getReflection(), Bar::getReflection());
    }

}

$testCase = new AssociationTest;
$testCase->run();