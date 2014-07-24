<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @mapper FooMapper(resource)
 *
 * @property       integer    $id         m:primary
 * @property       string     $text
 * @property       string     $empty
 * @property       string     $url        m:map(link)
 * @property       string     $email      m:map(email_address)
 * @property       DateTime   $time
 * @property       integer    $year       m:computed
 * @property       string     $ip
 * @property       integer    $mark
 * @property       Nested     $entity
 * @property       Nested[]   $collection
 * @property-read  string     $readonly
 */
class Simple extends \UniMapper\Entity
{

    /** @var string */
    public $publicProperty = "defaultValue";

    protected function computeYear()
    {
        if ($this->time !== null) {
            return (int) $this->time->format("Y");
        }
    }

}