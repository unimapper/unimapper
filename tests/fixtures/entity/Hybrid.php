<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FirstMapper(first_resource)
 * @mapper SecondMapper(second_resource)
 *
 * @property integer $id     m:map(FirstMapper:|SecondMapper:)            m:primary
 * @property string  $first  m:map(FirstMapper:|SecondMapper:customFirst)
 * @property integer $second m:map(SecondMapper:secondary)
 */
class Hybrid extends \UniMapper\Entity
{}