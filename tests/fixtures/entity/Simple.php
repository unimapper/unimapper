<?php

namespace UniMapper\Tests\Fixtures\Entity;

/**
 * @adapter FooAdapter(simple_resource)
 *
 * @property      integer  $id          m:primary m:map-by(simplePrimaryId)
 * @property      string   $text
 * @property      string   $empty
 * @property      string   $url         m:map-by(link)
 * @property      string   $email       m:map-by(email_address)
 * @property      DateTime $time
 * @property      integer  $year        m:computed
 * @property      string   $ip
 * @property      integer  $mark
 * @property      Nested   $entity
 * @property      Nested[] $collection  m:assoc(M:N) m:assoc-by(simpleId|simple_nested|nestedId)
 * @property      Remote[] $manyToMany  m:assoc(M:N) m:assoc-by(simpleId|simple_remote|remoteId)
 * @property      Remote   $manyToOne   m:assoc(N:1) m:assoc-by(remoteId)
 * @property      Remote   $oneToOne    m:assoc(1:1) m:assoc-by(remoteId)
 * @property-read string   $readonly
 * @property      array    $storedData  m:map-by(stored_data) m:map-filter(stringToArray|arrayToString)
 * @property      integer  $enumeration m:enum(self::ENUMERATION_*)
 */
class Simple extends \UniMapper\Entity
{

    const ENUMERATION_ONE = 1,
          ENUMERATION_TWO = 2;

    /** @var string */
    public $publicProperty = "defaultValue";

    protected function computeYear()
    {
        if ($this->time !== null) {
            return (int) $this->time->format("Y");
        }
    }

    public static function stringToArray($value)
    {
        return explode(',', $value);
    }

    public static function arrayToString($value)
    {
        return implode(',', $value);
    }

}