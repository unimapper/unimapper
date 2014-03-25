<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Basic entity, no mappers
$reflection = new UniMapper\Reflection\Entity("NoMapperEntity");
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
        "id" => new UniMapper\Reflection\Entity\Property('@property integer $id', $reflection),
        "text" => new UniMapper\Reflection\Entity\Property('@property string  $text', $reflection),
        "empty" => new UniMapper\Reflection\Entity\Property('@property string  $empty', $reflection)
    ),
    $reflection->getProperties()
);
Assert::null($reflection->getPrimaryProperty());


// Simple entity with mapper
$reflection = new UniMapper\Reflection\Entity("Entity");
Assert::isEqual(
    array('FirstMapper' => new UniMapper\Reflection\Mapper('FirstMapper(first_resource)', $reflection)),
    $reflection->getMappers()
);
Assert::false($reflection->isHybrid());
Assert::isEqual(
    array(
        "id" => new UniMapper\Reflection\Entity\Property('@property integer $id', $reflection),
        "text" => new UniMapper\Reflection\Entity\Property('@property string  $text', $reflection),
        "empty" => new UniMapper\Reflection\Entity\Property('@property string  $empty', $reflection),
        "entity" => new UniMapper\Reflection\Entity\Property('@property Entity   $entity', $reflection),
        "collection" => new UniMapper\Reflection\Entity\Property('@property Entity[] $collection', $reflection),
    ),
    $reflection->getProperties()
);
Assert::isEqual(
    new UniMapper\Reflection\Entity\Property('@property integer  $id         m:map(FirstMapper:) m:primary', $reflection),
    $reflection->getPrimaryProperty()
);


// Hybrid entity
$reflection = new UniMapper\Reflection\Entity("HybridEntity");
Assert::isEqual(
    array(
        'FirstMapper' => new UniMapper\Reflection\Mapper('FirstMapper(first_resource)', $reflection),
        'SecondMapper' => new UniMapper\Reflection\Mapper('SecondMapper(second_resource)', $reflection)
    ),
    $reflection->getMappers()
);
Assert::true($reflection->isHybrid());
Assert::isEqual(
    array(
        "id" => new UniMapper\Reflection\Entity\Property('@property integer $id     m:map(FirstMapper:|SecondMapper:) m:primary', $reflection),
        "first" => new UniMapper\Reflection\Entity\Property('@property string  $first  m:map(FirstMapper:|SecondMapper:customFirst)', $reflection),
        "second" => new UniMapper\Reflection\Entity\Property('@property integer $second m:map(SecondMapper:secondary)', $reflection)
    ),
    $reflection->getProperties()
);
Assert::isEqual(
    new UniMapper\Reflection\Entity\Property('@property integer $id     m:map(FirstMapper:|SecondMapper:) m:primary', $reflection),
    $reflection->getPrimaryProperty()
);