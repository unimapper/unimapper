<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FirstMapper(first_resource)
 *
 * @property integer    $id m:map(FirstMapper:) m:primary
 * @property string     $id m:map(FirstMapper:)
 */
class DuplicateProperty extends \UniMapper\Entity
{}