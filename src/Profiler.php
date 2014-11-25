<?php

namespace UniMapper;

class Profiler
{

    private static $captured = [];

    private static $enabled = false;

    private static $query;

    private static $results = [];

    public static function startQuery(Query $query)
    {
        self::flush();
        self::$query = $query;
        self::$enabled = true;
    }

    public static function log(Adapter\IQuery $query, $result)
    {
        if (self::$query) {
            self::$captured[] = $query->getRaw();
        } else {
            new Profiler\Result([$query->getRaw()]);
        }
    }

    public static function endQuery($result, $elapsed)
    {
        self::$results[] = new Profiler\Result(self::$captured, $elapsed, $result, self::$query);
        self::flush();
        self::$enabled = false;
    }

    public static function flush()
    {
        self::$captured = [];
        self::$query = null;
    }

    public static function getResults()
    {
        return self::$results;
    }

}