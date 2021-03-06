<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Exception\OptionException;

class Computed implements Reflection\Property\IOption
{

    const KEY = "computed";

    /** @var string */
    private $name;

    public function __construct(Reflection\Property $property)
    {
        $this->name = "compute" . ucfirst($property->getName());
        if (!method_exists($property->getReflection()->getClassName(), $this->name)) {
            throw new OptionException(
                "Computed method " . $this->name . " not found in "
                . $property->getReflection()->getClassName() . "!"
            );
        }
    }

    /**
     * Get method name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public static function create(
        Reflection\Property $property,
        $value = null,
        array $parameters = []
    ) {
        return new self($property);
    }

    public static function afterCreate(Reflection\Property $property, $option)
    {
        if ($property->hasOption(Map::KEY)
            || $property->hasOption(Enum::KEY)
            || $property->hasOption(Primary::KEY)
        ) {
            throw new OptionException(
                "Computed option can not be combined with mapping, enumeration "
                . "or primary options!"
            );
        }
    }

}