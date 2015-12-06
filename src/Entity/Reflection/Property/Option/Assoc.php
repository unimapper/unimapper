<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property;
use UniMapper\Entity\Reflection\Property\IOption;
use UniMapper\Exception\OptionException;

class Assoc implements IOption
{

    const KEY = "assoc";

    /** @var Reflection */
    private $targetReflection;

    /** @var Reflection */
    private $sourceReflection;

    /** @var array */
    private $definition = [];

    /** @var string */
    private $type;

    public function __construct(
        $type,
        Reflection $sourceReflection,
        Reflection $targetReflection,
        Property $property,
        array $definition = []
    ) {
        if (!$sourceReflection->hasAdapter()) {
            throw new OptionException(
                "Can not use associations while source entity "
                . $sourceReflection->getName()
                . " has no adapter defined!"
            );
        }

        if (!$targetReflection->hasAdapter()) {
            throw new OptionException(
                "Can not use associations while target entity "
                . $targetReflection->getName() . " has no adapter defined!"
            );
        }

        if (!in_array($property->getType(), [Property::TYPE_COLLECTION, Property::TYPE_ENTITY], true)) {
            throw new OptionException(
                "Property type must be collection or entity if association "
                . "defined!"
            );
        }

        $this->type = strtolower($type);
        $this->targetReflection = $targetReflection;
        $this->sourceReflection = $sourceReflection;
        $this->definition = $definition;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return Reflection
     */
    public function getTargetReflection()
    {
        return $this->targetReflection;
    }

    /**
     * @return Reflection
     */
    public function getSourceReflection()
    {
        return $this->sourceReflection;
    }

    /**
     * Cross-adapter association?
     *
     * @return bool
     */
    public function isRemote()
    {
        return $this->sourceReflection->getAdapterName()
        !== $this->targetReflection->getAdapterName();
    }

    public static function create(
        Property $property,
        $value = null,
        array $parameters = []
    ) {
        if (!$value) {
            throw new OptionException("Association definition required!");
        }

        if (isset($parameters[self::KEY . "-by"])) {
            $definition = explode("|", $parameters[self::KEY . "-by"]);
        } else {
            $definition = [];
        }

        return new self(
            $value,
            $property->getReflection(),
            Reflection::load($property->getTypeOption()),
            $property,
            $definition
        );
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