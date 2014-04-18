<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FirstMapper(first_resource)
 *
 * @property integer    $id         m:map(FirstMapper:) m:primary
 * @property string     $text       m:map(FirstMapper:)
 * @property string     $empty
 * @property string     $url        m:validate(url)
 * @property string     $email      m:validate(email)
 * @property DateTime   $time
 * @property integer    $year       m:computed
 * @property string     $ip         m:validate(ip)
 * @property integer    $mark       m:validate(mark)
 * @property NoMapper   $entity
 * @property NoMapper[] $collection
 */
class Simple extends \UniMapper\Entity
{

    public static function validateMark($value)
    {
        return $value >= 1 && $value <= 5;
    }

    protected function computeYear()
    {
        if ($this->time !== null) {
            return (int) $this->time->format("Y");
        }
    }

}