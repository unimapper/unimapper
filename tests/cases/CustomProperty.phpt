<?php
use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

$mapper = new Fixtures\Mapper\Simple("FooMapper");
$mapper->registerCustomMapper( 'custom', new Fixtures\Mapper\SampleEncoder() );

// Get name
Assert::same("FooMapper", $mapper->getName());

$email = "john.doe@example.com";
$url = "http://example.com";
$entity = new Fixtures\Entity\CustomProperty;
$entity->tags = $entity->tagsCustom = $entity->tagsSelf = $entity->tagsSelfFunction = $entity->tagsStatic = $entity->tagsStaticFunction = array('one','two');


// mapEntity()
Assert::isEqual($entity, $mapper->mapEntity("UniMapper\Tests\Fixtures\Entity\CustomProperty", ['tags' => 'one,two', 'tagsCustom' => 'one,two', 'tagsSelf' => 'one,two', 'tagsSelfFunction' => 'one,two']));

// unmapEntity()
$unMappedEntity = $mapper->unmapEntity($entity);
Assert::same('one,two', $unMappedEntity['tags']);
Assert::same('one,two', $unMappedEntity['tagsCustom']);
Assert::same('one,two', $unMappedEntity['tagsSelf']);
Assert::same('one,two', $unMappedEntity['tagsSelfFunction']);
Assert::same('one,two', $unMappedEntity['tagsStatic']);
Assert::same('one,two', $unMappedEntity['tagsStaticFunction']);