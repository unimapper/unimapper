<?php

namespace UniMapper;

use UniMapper\Exception\InvalidArgumentException;

class NamingConvention
{

    const ENTITY_MASK = 1;
    const REPOSITORY_MASK = 2;

    /** @var string */
    private static $masks = [
        self::ENTITY_MASK => "*",
        self::REPOSITORY_MASK => "*Repository"
    ];

    /**
     * Converts class to name
     *
     * @param string  $class
     * @param integer $type  Mask type
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function classToName($class, $type)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(
                "Class '" . $class . "' not found!"
            );
        }
        $class = self::_trimNamespace($class);

        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException(
                "Invalid mask type " . $type . "!"
            );
        }

        $mask = self::_trimNamespace(self::$masks[$type]);

        if ($mask === "*") {
            return $class;
        }

        preg_match("/" . str_replace("*", "(.*)", $mask) . "/", $class, $match);
        return $match[1];
    }

    /**
     * Converts name to class
     *
     * @param string  $name
     * @param integer $type
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function nameToClass($name, $type)
    {
        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException(
                "Invalid mask type " . $type . "!"
            );
        }
        return str_replace("*", $name, self::$masks[$type]);
    }

    private static function _isValidMask($mask)
    {
        if (substr_count($mask, "*") <> 1) {
            return false;
        }
        $mask = self::_trimNamespace($mask);
        return $mask === "*" || self::_startsWith($mask, "*")
            || self::_endsWith($mask, "*");
    }

    private static function _trimNamespace($class)
    {
        $parts = explode("\\", $class);
        return end($parts);
    }

    public static function getMask($type)
    {
        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException(
                "Invalid mask type " . $type . "!"
            );
        }
        return self::$masks[$type];
    }

    public static function setMask($mask, $type)
    {
        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException(
                "Invalid mask type " . $type . "!"
            );
        }
        if (!self::_isValidMask($mask)) {
            throw new InvalidArgumentException(
                "Invalid mask '" . $mask . "'!"
            );
        }
        self::$masks[$type] = $mask;
    }

    private static function _startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    private static function _endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}