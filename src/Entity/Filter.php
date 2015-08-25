<?php

namespace UniMapper\Entity;

use UniMapper\Exception;

class Filter
{

    const _OR = "or",
        EQUAL = "=",
        NOT = "!",
        LIKE = "LIKE",
        GREATER = ">",
        LESS = "<",
        GREATEREQUAL = ">=",
        LESSEQUAL = "<=";

    /** @var array */
    public static $modifiers = [
        self::EQUAL,
        self::NOT,
        self::LIKE,
        self::GREATER,
        self::LESS,
        self::GREATEREQUAL,
        self::LESSEQUAL
    ];

    /**
     * Is filter group
     *
     * @param array $value
     *
     * @return bool
     */
    public static function isGroup(array $value)
    {
        return $value === array_values($value) || isset($value[self::_OR]);
    }

    /**
     * Merge two filters
     *
     * @param Reflection    $reflection
     * @param array         $current
     * @param array         $new
     * @param callable|null $itemCb     Callback on filter item
     *
     * @return array
     *
     * @throws Exception\FilterException
     * @throws Exception\InvalidArgumentException
     * @throws \Exception
     */
    public static function merge(
        Reflection $reflection,
        array $current,
        array $new,
        callable $itemCb = null
    ) {
        if (self::isGroup($new)) {
            // Filter group

            foreach ($new as $modifier => $item) {

                if (($modifier !== self::_OR && !is_int($modifier))
                    || !is_array($item) || empty($item)
                ) {
                    throw new Exception\FilterException(
                        "Invalid filter group structure!"
                    );
                }

                $group = self::merge($reflection, [], $item, $itemCb);
                if ($modifier === self::_OR) {
                    $current[] = [$modifier => $group];
                } else {
                    $current[] = $group;
                }
            }
        } else {
            // Filter item

            foreach ($new as $name => $item) {

                if (!is_array($item) || !is_string($name)) {
                    throw new Exception\FilterException(
                        "Invalid filter structure!"
                    );
                }

                if (!$reflection->hasProperty($name)) {
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
                            && $property->getTypeOption() !== Reflection\Property::TYPE_ARRAY
                            && in_array($modifier, [self::EQUAL, self::NOT], true)
                        ) {
                            // Array values

                            foreach ($value as $index => $valueItem) {
                                $property->validateValueType($valueItem);
                            }
                        } else {
                            $property->validateValueType($value);
                        }
                    } catch (Exception\InvalidArgumentException $e) {
                        throw new Exception\FilterException($e->getMessage());
                    }
                }

                if ($itemCb) {
                    $item = $itemCb($name, $item);
                    if ($item) {
                        $key = key($item);
                        $current[$key] = $item[$key];
                    }
                } else {
                    $current[$name] = $item;
                }
            }
        }

        return $current;
    }

}