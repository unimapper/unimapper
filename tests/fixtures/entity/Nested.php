<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FooMapper(resource)
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