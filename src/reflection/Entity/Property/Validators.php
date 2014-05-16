<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Exceptions\PropertyException,
    UniMapper\Reflection;

class Validators
{

    /** @var array */
    private $callbacks = array();

    public function __construct($definition, $rawDefinition, Reflection\Entity $entityReflection)
    {
        $definition = trim($definition);
        if ($definition === "") {
            throw new PropertyException(
                "Validation definition can not be empty!",
                $entityReflection,
                $rawDefinition
            );
        }

        foreach (explode("|", $definition) as $name) {

            if (isset($this->callbacks[$name])) {
                throw new PropertyException(
                    "Duplicate validation definition with name " . $name . "!",
                    $entityReflection,
                    $rawDefinition
                );
            }

            $class = $entityReflection->getClassName();
            $methodName = "validate" . ucfirst($name);
            if (!method_exists($class, $methodName)) {
                throw new PropertyException(
                    "Validation method " . $methodName . " not defined in " . $class . "!",
                    $entityReflection,
                    $rawDefinition
                );
            }
            $this->callbacks[$name] = [$class, $methodName];
        }
    }

    public function getCallbacks()
    {
        return $this->callbacks;
    }

}