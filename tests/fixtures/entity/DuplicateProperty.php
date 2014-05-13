<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FooMapper(resource)
 *
 * @property integer    $id m:map(FooMapper:) m:primary
 * @property string     $id m:map(FooMapper:)
 */
class DuplicateProperty extends \UniMapper\Entity
{}