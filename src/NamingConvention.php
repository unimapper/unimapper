<?php

namespace UniMapper;

use UniMapper\Exception\InvalidArgumentException;

class NamingConvention
{

    const ENTITY_MASK = 1;
    const REPOSITORY_MASK = 2;

    /** @var string */
    private static $masks = [
        self::ENTITY_MASK => "Model\Entity\*",
        self::REPOSITORY_MASK => "Model\Repository\*Repository"
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
            throw new InvalidArgumentException("Class '" . $class . "' not found!");
        }
        $class = self::trimNamespace($class);

        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException("Invalid mask type " . $type . "!");
        }

        $mask = self::trimNamespace(self::$masks[$type]);

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
            throw new InvalidArgumentException("Invalid mask type " . $type . "!");
        }
        return str_replace("*", $name, self::$masks[$type]);
    }

    private static function isValidMask($mask)
    {
        if (substr_count($mask, "*") <> 1) {
            return false;
        }
        $mask = self::trimNamespace($mask);
        return $mask === "*" || self::startsWith($mask, "*")
            || self::endsWith($mask, "*");
    }

    private static function trimNamespace($class)
    {
        $parts = explode("\\", $class);
        return end($parts);
    }

    public static function getMask($type)
    {
        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException("Invalid mask type " . $type . "!");
        }
        return self::$masks[$type];
    }

    public static function setMask($mask, $type)
    {
        if (!isset(self::$masks[$type])) {
            throw new InvalidArgumentException("Invalid mask type " . $type . "!");
        }
        if (!self::isValidMask($mask)) {
            throw new InvalidArgumentException("Invalid mask '" . $mask . "'!");
        }
        self::$masks[$type] = $mask;
    }

    private static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    private static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}