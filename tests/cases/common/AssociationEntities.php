<?php

/**
 * @adapter FooAdapter
 *
 * @property int $id m:primary m:map-filter(in|out)
 */
class Foo extends \UniMapper\Entity
{
    public static function out($val)
    {
        return (string) $val;
    }

    public static function in($val)
    {
        return (int) $val;
    }
}

/**
 * @adapter BarAdapter
 *
 * @property int    $id   m:primary m:map-filter(in|out)
 * @property string $text m:map(text_unmapped)
 */
class Bar extends \UniMapper\Entity
{
    public static function out($val)
    {
        return (string) $val;
    }

    public static function in($val)
    {
        return (int) $val;
    }
}