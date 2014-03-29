<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FirstMapper(first_resource)
 *
 * @property integer                                    $id         m:map(FirstMapper:) m:primary
 * @property string                                     $text       m:map(FirstMapper:)
 * @property string                                     $empty
 * @property UniMapper\Tests\Fixtures\Entity\NoMapper   $entity
 * @property UniMapper\Tests\Fixtures\Entity\NoMapper[] $collection
 */
class Simple extends \UniMapper\Entity
{}