<?php

use Tester\Assert;
use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';


// Missing mapper definition
Assert::exception(function() {
    new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoMapper");
}, "UniMapper\Exceptions\PropertyException", "No mapper defined!");


// Simple entity with mapper
$reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
Assert::isEqual(
    array('FooMapper' => new Reflection\Mapper('FooMapper(resource)', $reflection)),
    $reflection->getMapperReflection()
);
Assert::isEqual(
    array(
        "id" => new Reflection\Entity\Property('@property integer $id', $reflection),
        "text" => new Reflection\Entity\Property('@property string $text', $reflection),
        "empty" => new Reflection\Entity\Property('@property string $empty', $reflection),
        "entity" => new Reflection\Entity\Property('@property NoMapper $entity', $reflection),
        "collection" => new Reflection\Entity\Property('@property NoMapper[] $collection', $reflection),
    ),
    $reflection->getProperties()
);
Assert::isEqual(
    new Reflection\Entity\Property('@property integer $id m:primary', $reflection),
    $reflection->getPrimaryProperty()
);


// Duplicate properties
Assert::exception(function() {
    new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\DuplicateProperty");
}, "UniMapper\Exceptions\PropertyException", 'Duplicate property name $id');