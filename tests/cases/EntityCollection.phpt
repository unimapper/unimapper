<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$entity = new Fixtures\Entity\Simple;
$entity->text = "test";

$collection = new UniMapper\EntityCollection(get_class($entity));

$collection[] = $entity;
Assert::same("test", $collection[0]->text);

$entity->text = "foo";
$collection[] = $entity;

foreach ($collection as $entity) {
    Assert::type(get_class($entity), $entity);
    Assert::same("foo", $entity->text);
}


// mergeByPrimary()
$firstColEntity1 = new Fixtures\Entity\Hybrid;
$firstColEntity1->id = 1;
$firstColEntity2 = new Fixtures\Entity\Hybrid;
$firstColEntity2->id = 2;
$firstColEntity3 = new Fixtures\Entity\Hybrid;
$firstColEntity3->id = 3;
$firstCol = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Hybrid");
$firstCol[] = $firstColEntity1;
$firstCol[] = $firstColEntity2;
$firstCol[] = $firstColEntity3;

$secColEntity1 = new Fixtures\Entity\Hybrid;
$secColEntity1->id = 2;
$secCol = new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Hybrid");
$secCol[] = $secColEntity1;

Assert::isEqual(
    $secCol,
    UniMapper\EntityCollection::mergeByPrimary($firstCol, $secCol)
);