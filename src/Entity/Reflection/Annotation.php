<?php

namespace UniMapper\Entity\Reflection;

use UniMapper\Entity\Reflection\Property\Option\Assoc;
use UniMapper\Entity\Reflection\Property\Option\Computed;
use UniMapper\Entity\Reflection\Property\Option\Enum;
use UniMapper\Entity\Reflection\Property\Option\Map;
use UniMapper\Entity\Reflection\Property\Option\Primary;
use UniMapper\Exception;

class Annotation
{

    /** @var array $options */
    private static $options = [
        Assoc::KEY => 'UniMapper\Entity\Reflection\Property\Option\Assoc',
        Computed::KEY => 'UniMapper\Entity\Reflection\Property\Option\Computed',
        Enum::KEY => 'UniMapper\Entity\Reflection\Property\Option\Enum',
        Map::KEY => 'UniMapper\Entity\Reflection\Property\Option\Map',
        Primary::KEY => 'UniMapper\Entity\Reflection\Property\Option\Primary'
    ];

    /**
     * Register new property option
     *
     * @param string $key
     * @param string $class
     *
     * @throws Exception\AnnotationException
     */
    public static function registerOption($key, $class)
    {
        if (empty($key)) {
            throw new Exception\AnnotationException(
                "Option key can not be empty!"
            );
        }
        if (!class_exists($class)) {
            throw new Exception\AnnotationException(
                "Class " . $class . " not found!"
            );
        }
        if (!in_array("UniMapper\Entity\Reflection\Property\IOption", class_implements($class))) {
            throw new Exception\AnnotationException(
                "Class " . $class
                . " should implement UniMapper\Entity\Reflection\Property\IOption!"
            );
        }
        self::$options[$key] = $class;
    }

    public static function getRegisteredOptions()
    {
        return self::$options;
    }

    /**
     * Find adapter
     *
     * @param string $definition
     *
     * @return array|false [name, resource]
     *
     * @throws Exception\AnnotationException
     */
    public static function parseAdapter($definition)
    {
        preg_match_all('/\s*@adapter\s+([a-z-]+)(?:\(\s*([^)\s]+)\s*\))?/i', $definition, $matched);

        if (empty($matched[0])) {
            return false;
        }

        if (count($matched[0]) > 1) {
            throw new Exception\AnnotationException(
                "Only one adapter definition allowed!",
                $matched[0][1]
            );
        }

        return [$matched[1][0], $matched[2][0]];
    }

    /**
     * Find all properties
     *
     * @param string $definition
     *
     * @return array
     */
    public static function parseProperties($definition)
    {
        preg_match_all(
            '/\h*\*\h*@property(-read)?\h+(\S+)\h+\$(\S+)([^\v]*)/i',
            $definition,
            $matched,
            PREG_SET_ORDER
        );
        return $matched;
    }

    /**
     * Find all property options
     *
     * @param string $definition
     *
     * @return array
     *
     * @throws Exception\AnnotationException
     */
    public static function parseOptions($definition)
    {
        preg_match_all('/m:([a-z-]+)(?:\(([^)]*)\))?/i', $definition, $matched, PREG_SET_ORDER);

        $result = [];
        foreach ($matched as $match) {

            if (array_key_exists($match[1], $result)) {
                throw new Exception\AnnotationException(
                    "Duplicate option '" . $match[1] . "'!"
                );
            }

            $result[$match[1]] = isset($match[2]) ? trim($match[2]) : null;
        }
        return $result;
    }

}
