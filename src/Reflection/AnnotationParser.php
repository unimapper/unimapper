<?php

namespace UniMapper\Reflection;

use UniMapper\Exception;

class AnnotationParser
{

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
     */
    public static function parseOptions($definition)
    {
        preg_match_all('/m:([a-z-]+)(?:\(([^)]*)\))?/i', $definition, $matched, PREG_SET_ORDER);

        $result = [];
        foreach ($matched as $match) {
            $result[$match[1]] = isset($match[2]) ? $match[2] : null;
        }
        return $result;
    }

}
