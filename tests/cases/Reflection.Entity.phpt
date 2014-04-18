<?php

use Tester\Assert;
use UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';


// Basic entity, no mappers
$reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\NoMapper");
Assert::same(array(), $reflection->getMappers());
Assert::false($reflection->isHybrid());
Assert::true($reflection->hasProperty("id"));
Assert::false($reflection->hasProperty("foo"));
Assert::type("UniMapper\Reflection\Entity\Property", $reflection->getProperty("id"));
Assert::exception(function() use ($reflection) {
    $reflection->getProperty("foo");
}, "Exception", "Unknown property foo!");
Assert::isEqual(
    array(
        "id" => new Reflection\Entity\Property('@property integer $id', $reflection),
        "text" => new Reflection\Entity\Property('@property string  $text', $reflection),
        "empty" => new Reflection\Entity\Property('@property string  $empty', $reflection)
    ),
    $reflection->getProperties()
);
Assert::null($reflection->getPrimaryProperty());


// Simple entity with mapper
$reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple");
Assert::isEqual(
    array('FirstMapper' => new Reflection\Mapper('FirstMapper(first_resource)', $reflection)),
    $reflection->getMappers()
);
Assert::false($reflection->isHybrid());
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
    new Reflection\Entity\Property('@property integer  $id         m:map(FirstMapper:) m:primary', $reflection),
    $reflection->getPrimaryProperty()
);


// Hybrid entity
$reflection = new Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Hybrid");
Assert::isEqual(
    array(
        'FirstMapper' => new Reflection\Mapper('FirstMapper(first_resource)', $reflection),
        'SecondMapper' => new Reflection\Mapper('SecondMapper(second_resource)', $reflection)
    ),
    $reflection->getMappers()
);
Assert::true($reflection->isHybrid());
Assert::isEqual(
    array(
        "id" => new Reflection\Entity\Property('@property integer $id m:map(FirstMapper:|SecondMapper:) m:primary', $reflection),
        "first" => new Reflection\Entity\Property('@property string $first m:map(FirstMapper:|SecondMapper:customFirst)', $reflection),
        "second" => new Reflection\Entity\Property('@property integer $second m:map(SecondMapper:secondary)', $reflection)
    ),
    $reflection->getProperties()
);
Assert::isEqual(
    new Reflection\Entity\Property('@property integer $id m:map(FirstMapper:|SecondMapper:) m:primary', $reflection),
    $reflection->getPrimaryProperty()
);