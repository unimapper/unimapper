<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exception;

/**
 * Property mapping definition like m:map(name='column' filter=in_fnc|out_fnc)
 */
class Mapping
{

    const EXPRESSION = "#m:map\((.*?)\)#s";

    /** @var string $name Mapped name */
    private $name;

    /** @var callable $filterOut */
    private $filterIn;

    /** @var callable $filterIn */
    private $filterOut;

    /** @var  array */
    private $options;

    public function __construct($entityClass, $definition)
    {
        foreach (explode(";", $definition) as $parameter) {

            list($name, $value) = array_pad(explode('=', $parameter, 2), 2, null);
            switch (strtolower($name)) {
            case "name":
                $this->name = trim($value, "'");
                break;
            case "filter":

                list($in, $out) = explode("|", $value);
                $this->filterIn = $this->_createCallable($entityClass, $in);
                $this->filterOut = $this->_createCallable($entityClass, $out);
                break;
            default:
                $this->options[$name] = trim($value, "'");
            }
        }
    }

    private function _createCallable($entityClass, $definition)
    {
        if (method_exists($entityClass, $definition)) {
            return [$entityClass, $definition];
        } elseif (is_callable($definition)) {
            return $definition;
        }
        throw new Exception\DefinitionException(
            "Invalid mapping definition. Filter must contain valid callbacks "
            . "or entity function name but '" . $definition . "' given!"
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

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

}