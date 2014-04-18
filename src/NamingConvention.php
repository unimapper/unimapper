<?php

namespace UniMapper;

use UniMapper\Exceptions\InvalidArgumentException;

class NamingConvention
{

    /** @var string */
    public static $entityMask = "Model\Entity\*";

    /** @var string */
    public static $repositoryMask = "Model\Repository\*Repository";

    public static function classToName($class, $mask)
    {
        if (!self::isValidMask($mask)) {
            throw new InvalidArgumentException("Invalid mask '" . $mask . "'!");
        }
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class '" . $class . "' not found!");
        }

        $class = self::trimNamespace($class);
        $mask = self::trimNamespace($mask);

        if ($mask === "*") {
            return $class;
        }
        return explode("*", $class)[0];
    }

    public static function nameToClass($name, $mask)
    {
        if (!self::isValidMask($mask)) {
            throw new InvalidArgumentException("Invalid mask '" . $mask . "'!");
        }
        return str_replace("*", $name, $mask);
    }

    public static function isValidMask($mask)
    {
        if (substr_count($mask, "*") <> 1) {
            return false;
        }
        $mask = self::trimNamespace($mask);
        if ($mask === "*" || self::startsWith($mask, "*") || self::endsWith($mask, "*")) {
            return true;
        }
        return false;
    }

    public static function trimNamespace($class)
    {
        $parts = explode("\\", $class);
        return end($parts);
    }

    public static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    public static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}