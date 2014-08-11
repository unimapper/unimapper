<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exception\PropertyException,
    UniMapper\Reflection;

/**
 * Property mapping definition
 */
class Mapping
{

    /** @var string $name Mapped name */
    private $name;

    /** @var callable $filterOut */
    private $filterIn;

    /** @var callable $filterIn */
    private $filterOut;

    /** @var \UniMapper\Reflection\Entity $entityReflection */
    private $entityReflection;

    /** @var string $rawDefinition */
    private $rawDefinition;

    public function __construct($definition, $rawDefinition,
        Reflection\Entity $entityReflection
    ) {
        $this->rawDefinition = $rawDefinition;
        $this->entityReflection = $entityReflection;

        foreach (explode(";", $definition) as $parameter) {

            list($name, $value) = array_pad(explode('=', $parameter, 2), 2, null);
            switch (strtolower($name)) {
            case "name":
                $this->name = trim($value, "'");
                break;
            case "filter":

                list($in, $out) = explode("|", $value);
                $this->filterIn = $this->_createCallable($in);
                $this->filterOut = $this->_createCallable($out);
                break;
            default:
                throw new PropertyException(
                    "Unknown mapping definition '" . $name . "'!",
                    $entityReflection,
                    $rawDefinition
                );
            }
        }
    }

    private function _createCallable($definition)
    {
        if (method_exists($this->entityReflection->getClassName(), $definition)) {
            return [$this->entityReflection->getClassName(), $definition];
        } elseif (is_callable($definition)) {
            return $definition;
        }
        throw new PropertyException(
            "Invalid mapping definition. Filter must contain valid callbacks "
            . "or entity function name but '" . $definition . "' given!",
            $this->entityReflection,
            $this->rawDefinition
        );
    }

    public function getName()
    {
        return $this->name;
    }

    public function getFilterIn()
    {
        return $this->filterIn;
    }

    public function getFilterOut()
    {
        return $this->filterOut;
    }

}