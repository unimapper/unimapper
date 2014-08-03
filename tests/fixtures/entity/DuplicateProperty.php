<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter FooAdapter(resource)
 *
 * @property integer    $id m:map(FooAdapter:) m:primary
 * @property string     $id m:map(FooAdapter:)
 */
class DuplicateProperty extends \UniMapper\Entity
{}