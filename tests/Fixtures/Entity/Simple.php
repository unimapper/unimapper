<?php

namespace UniMapper\Tests\Fixtures\Entity;

use UniMapper\Association\Multi;
use UniMapper\Association\Single;
use UniMapper\Entity\Filter;
use UniMapper\Entity\Reflection\Property\Option\Assoc;
use UniMapper\Query\Select;


Assoc::registerFilter("sortAndLimit", function (Multi $assoc, $orderBy = "id", $limit = 10) {
    $assoc->limit($limit)->orderBy("id");
});

Assoc::registerFilter("textLikeFoo", function (Single $assoc) {
    $assoc->setFilter(["text" => [Filter::EQUAL => "foo"]]);
});

/**
 * @adapter FooAdapter(simple_resource)
 *
 * @property      integer  $id          m:primary m:map-by(simplePrimaryId)
 * @property      string   $text
 * @property      string   $empty
 * @property      string   $url         m:map-by(link)
 * @property      string   $email       m:map-by(email_address)
 * @property      DateTime $time
 * @property      Date     $date
 * @property      integer  $year        m:computed
 * @property      string   $ip
 * @property      integer  $mark
 * @property      Nested   $entity
 * @property      Nested[] $collection  m:assoc(M:N) m:assoc-by(simpleId|simple_nested|nestedId)
 * @property      Nested[] $oneToMany   m:assoc(1:N) m:assoc-by(simplePrimaryId)
 * @property      Remote[] $oneToManyRemote m:assoc(1:N) m:assoc-by(simplePrimaryId)
 * @property      Remote[] $manyToMany  m:assoc(M:N) m:assoc-by(simpleId|simple_remote|remoteId)
 * @property      Remote[] $mmFilter    m:assoc(M:N) m:assoc-by(simpleId|simple_remote|remoteId) m:assoc-filter-sortAndLimit(id|10)
 * @property      Remote   $manyToOne   m:assoc(N:1) m:assoc-by(remoteId)
 * @property      Remote   $oneToOne    m:assoc(1:1) m:assoc-by(remoteId)
 * @property      Remote   $ooFilter    m:assoc(1:1) m:assoc-by(remoteId) m:assoc-filter-textLikeFoo
 * @property-read string   $readonly
 * @property      array    $storedData  m:map-by(stored_data) m:map-filter(stringToArray|arrayToString)
 * @property      integer  $enumeration m:enum(self::ENUMERATION_*)
 * @property      int      $disabledMap m:map(false)
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