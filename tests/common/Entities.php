<?php

/**
 * @property integer $id
 * @property string  $text
 * @property string  $empty
 */
class NoMapperEntity extends \UniMapper\Entity
{}

/**
 * @mapper FirstMapper(first_resource)
 *
 * @property integer  $id         m:map(FirstMapper:) m:primary
 * @property string   $text       m:map(FirstMapper:)
 * @property string   $empty
 * @property Entity   $entity
 * @property Entity[] $collection
 */
class Entity extends \UniMapper\Entity
{}

/**
 * @mapper FirstMapper(first_resource)
 * @mapper SecondMapper(second_resource)
 *
 * @property integer $id     m:map(FirstMapper:|SecondMapper:) m:primary
 * @property string  $first  m:map(FirstMapper:|SecondMapper:customFirst)
 * @property integer $second m:map(SecondMapper:secondary)
 */
class HybridEntity extends \UniMapper\Entity
{}