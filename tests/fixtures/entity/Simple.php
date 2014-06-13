<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FooMapper(resource)
 *
 * @property       integer    $id         m:primary
 * @property       string     $text
 * @property       string     $empty
 * @property       string     $url        m:validate(url)   m:map(link)
 * @property       string     $email      m:validate(email) m:map(email_address)
 * @property       DateTime   $time
 * @property       integer    $year       m:computed
 * @property       string     $ip         m:validate(ip)
 * @property       integer    $mark       m:validate(mark)
 * @property       Nested     $entity
 * @property       Nested[]   $collection
 * @property-read  string     $readonly
 */
class Simple extends \UniMapper\Entity
{

    /** @var string */
    public $localProperty = "defaultValue";

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