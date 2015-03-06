<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter FooAdapter(resource)
 *
 * @property integer  $id         m:primary
 * @property string   $text
 * @property Simple[] $collection
 * @property Simple   $entity
 */
class Nested extends \UniMapper\Entity
{

    public $publicProperty = "defaultValue";

}