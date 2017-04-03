<?php

namespace UniMapper\Entity;

use UniMapper\Exception;

class Filter
{

    const _OR = "%or",
        EQUAL = "=",
        NOT = "!",
        START = "START",
        END = "END",
        CONTAIN = "CONTAIN",
        GREATER = ">",
        LESS = "<",
        GREATEREQUAL = ">=",
        LESSEQUAL = "<=";

    /** @var array */
    public static $modifiers = [
        self::EQUAL,
        self::NOT,
        self::START,
        self::END,
        self::CONTAIN,
        self::GREATER,
        self::LESS,
        self::GREATEREQUAL,
        self::LESSEQUAL
    ];

    /**
     * @param array $original
     * @param array $new
     *
     * @return array
     *
     * @throws Exception\FilterException
     */
    public static function merge(array $original, array $new)
    {
        if (empty($new)) {
            return $original;
        }
        if (empty($original)) {
            return $new;
        }

        return array_merge([$original], [$new]);
    }

    /**
     * Is it filter with groups?
     *
     * @param array $value
     *
     * @return bool
     */
    public static function isGroup(array $value)
    {
        return $value === array_values($value)
            || (isset($value[self::_OR]) && count($value) === 1);
    }

    /**
     * Merge two filters
     *
     * @param Reflection $reflection
     * @param array      $filter
     *
     * @return array
     *
     * @throws Exception\FilterException
     */
    public static function validate(Reflection $reflection, array $filter)
    {
        if (self::isGroup($filter)) {
            // Filter group

            foreach ($filter as $modifier => $item) {

                if (!is_array($item) || empty($item)) {
                    throw new Exception\FilterException(
                        "Invalid filter group structure!"
                    );
                }

                self::validate($reflection, $item);
            }
        } else {
            // Filter item

            foreach ($filter as $name => $item) {

                if (!is_array($item) || !is_string($name)) {
                    throw new Exception\FilterException(
                        "Invalid filter structure!"
                    );
                }

                if (!$reflection->hasProperty($name)) {
                    if (strpos($name, '.' ) !== false) {
                        continue;
                    }
                    throw new Exception\FilterException(
                        "Undefined property name '" . $name . "' used in filter!"
                    );
                }
                $property = $reflection->getProperty($name);

                foreach ($item as $modifier => $value) {

                    if (!in_array($modifier, self::$modifiers, true)) {
                        throw new Exception\FilterException(
                            "Invalid filter modifier '" . $modifier . "'!"
                        );
                    }

                    if ($property->hasOption(Reflection\Property::OPTION_ASSOC)
                        || $property->hasOption(Reflection\Property::OPTION_COMPUTED)
                        || $property->getType() === Reflection\Property::TYPE_COLLECTION
                        || $property->getType() === Reflection\Property::TYPE_ENTITY
                    ) {
                        throw new Exception\FilterException(
                            "Filter can not be used with associations, computed, collections and entities!"
                        );
                    }

                    // Validate value type
                    try {

                        if (is_array($value)
                            && $property->getType() !== Reflection\Property::TYPE_ARRAY
                            && in_array($modifier, [self::EQUAL, self::NOT], true)
                        ) {
                            // Array values

                            foreach ($value as $index => $valueItem) {
                                $property->validateValueType($property->convertValue($valueItem));
                            }
                        } else {
                            $property->validateValueType($property->convertValue($value));
                        }
                    } catch (Exception\InvalidArgumentException $e) {
                        throw new Exception\FilterException($e->getMessage());
                    }
                }
            }
        }
    }

}