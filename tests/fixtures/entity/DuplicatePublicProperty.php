<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FooMapper(resource)
 *
 * @property integer $id
 */
class DuplicatePublicProperty extends \UniMapper\Entity
{

    public $id;

}