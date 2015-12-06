<?php

namespace UniMapper\Query;

use UniMapper\Association;
use UniMapper\Exception;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property\Option\Assoc;

trait Selectable
{

    /** @var array */
    protected $adapterAssociations = [];

    /** @var array */
    protected $remoteAssociations = [];

    /** @var array */
    protected $selection = [];

    public function associate($args)
    {
        foreach (func_get_args() as $arg) {

            if (!is_array($arg)) {
                $arg = [$arg];
            }

            foreach ($arg as $name) {

                if (!$this->reflection->hasProperty($name)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined on entity "
                        . $this->reflection->getClassName() . "!"
                    );
                }

                $property = $this->reflection->getProperty($name);
                if (!$property->hasOption(Assoc::KEY)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined as association"
                        . " on entity " . $this->reflection->getClassName()
                        . "!"
                    );
                }

                $option = $property->getOption(Assoc::KEY);

                if ($option->getSourceReflection()->getAdapterName() === $option->getTargetReflection()->getAdapterName()) {
                    $this->adapterAssociations[$name] = $option;
                } else {
                    $this->remoteAssociations[$name] = Association::create(
                        $option
                    );
                }
            }
        }

        return $this;
    }

    public function select($args)
    {
        foreach (func_get_args() as $arg) {

            if (!is_array($arg)) {
                $arg = [$arg];
            }

            foreach ($arg as $name) {

                if (!$this->reflection->hasProperty($name)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined on entity "
                        . $this->reflection->getClassName() . "!"
                    );
                }

                $property = $this->reflection->getProperty($name);
                if ($property->hasOption(Reflection\Property\Option\Assoc::KEY)
                    || $property->hasOption(Reflection\Property\Option\Computed::KEY)
                    || ($property->hasOption(Reflection\Property\Option\Map::KEY)
                        && !$property->getOption(Reflection\Property\Option\Map::KEY))
                ) {
                    throw new Exception\QueryException(
                        "Associations, computed and properties with disabled mapping can not be selected!"
                    );
                }

                if (!array_search($name, $this->selection)) {
                    $this->selection[] = $name;
                }
            }
        }

        return $this;
    }

}