<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter FooAdapter(resource)
 *
 * @property integer $id m:primary
 * @property string  $id
 */
class DuplicateProperty extends \UniMapper\Entity
{}