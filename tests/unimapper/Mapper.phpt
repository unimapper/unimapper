<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/TestMapper.php';

/**
 * @property integer $id
 * @property string  $text
 */
class NoMapperEntity extends \UniMapper\Entity
{}

Assert::type("UniMapper\Mapper", new TestMapper);

// Property not exists
Assert::exception(function() {
    $entity = new NoMapperEntity;
    $mapper = new TestMapper;
    $mapper->getResource($entity->getReflection());
}, "UniMapper\Exceptions\MapperException", "Entity does not define mapper TestMapper!");