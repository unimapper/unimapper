<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property;
use UniMapper\Entity\Reflection\Property\IOption;
use UniMapper\Exception\AssociationException;
use UniMapper\Exception\OptionException;

class Assoc implements IOption
{

    const KEY = "assoc";

    /** @var array $types */
    private static $types = [
        "M:N" => "ManyToMany",
        "M<N" => "ManyToMany",
        "M>N" => "ManyToMany",
        "N:1" => "ManyToOne",
        "1:1" => "OneToOne",
        "1:N" => "OneToMany"
    ];

    /** @var array $filters List of callable */
    private static $filters = [];

    public static function registerFilter($name, callable $callback)
    {
        self::$filters[$name] = $callback;
    }

    public static function create(
        Property $property,
        $value = null,
        array $parameters = []
    ) {
        if (!in_array($property->getType(), [Property::TYPE_COLLECTION, Property::TYPE_ENTITY], true)) {
            throw new OptionException(
                "Property type must be collection or entity if association "
                . "defined!"
            );
        }

        if (!$value) {
            throw new OptionException("Association definition required!");
        }

        if (!isset($parameters[self::KEY . "-by"])) {
            throw new OptionException("You must define association by!");
        }

        $class = 'UniMapper\Association\\' . self::$types[$value];

        try {

            $association = new $class(
                $property->getName(),
                $property->getEntityReflection(),
                Reflection::load($property->getTypeOption()),
                explode("|", $parameters[self::KEY . "-by"]),
                $value === "M<N" ? false : true
            );
        } catch (AssociationException $e) {
            throw new OptionException($e->getMessage(), $e->getCode(), $e);
        }

        // Filters
        if ($filters = preg_grep("/"  . self::KEY . "-filter-[aA-zZ]*/", array_keys($parameters))) {

            if (count($filters) > 1) {
                throw new OptionException(
                    "Only one association filter can be set!"
                );
            }

            $name = substr($filters[1], strlen(self::KEY . "-filter-"));
            if (!isset(self::$filters[$name])) {
                throw new OptionException(
                    "Association filter " . $name . " not is registered!"
                );
            }

            $args = explode("|", $parameters[$filters[1]]);

            array_unshift($args, $association);

            // Apply filter on association
            call_user_func_array(self::$filters[$name], $args);
        }

        return $association;
    }

    public static function afterCreate(Property $property, $option)
    {
        if ($property->hasOption(Map::KEY)
            || $property->hasOption(Enum::KEY)
            || $property->hasOption(Computed::KEY)
        ) {
            throw new OptionException(
                "Association can not be combined with mapping, computed or "
                . "enumeration!"
            );
        }
    }

}