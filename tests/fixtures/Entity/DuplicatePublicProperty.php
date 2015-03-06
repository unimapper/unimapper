<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter FooAdapter(resource)
 *
 * @property integer $id
 */
class DuplicatePublicProperty extends \UniMapper\Entity
{

    public $id;

}