<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FirstMapper(first_resource)
 *
 * @property integer                                    $id         m:map(FirstMapper:) m:primary
 * @property string                                     $text       m:map(FirstMapper:)
 * @property string                                     $empty
 * @property string                                     $url        m:validate(url)
 * @property string                                     $email      m:validate(email)
 * @property DateTime                                   $time
 * @property string                                     $ip         m:validate(ip)
 * @property integer                                    $mark       m:validate(mark)
 * @property UniMapper\Tests\Fixtures\Entity\NoMapper   $entity
 * @property UniMapper\Tests\Fixtures\Entity\NoMapper[] $collection
 */
class Simple extends \UniMapper\Entity
{

    public static function validateMark($value)
    {
        return $value >= 1 && $value <= 5;
    }

}