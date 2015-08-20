<?php

namespace UniMapper\Query;

use UniMapper\Association;
use UniMapper\Entity\Filter;
use UniMapper\Exception;
use UniMapper\Entity\Reflection;

trait Selectable
{

    /** @var array */
    protected $associations = [
        "local" => [],
        "remote" => []
    ];

    /** @var array */
    protected $selection = [];

    public function associate($args)
    {
        foreach (func_get_args() as $arg) {

            if (!is_array($arg)) {
                $arg = [$arg];
            }

            foreach ($arg as $name) {

                if (!$this->entityReflection->hasProperty($name)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined on entity "
                        . $this->entityReflection->getClassName() . "!"
                    );
                }

                $property = $this->entityReflection->getProperty($name);
                if (!$property->hasOption(Reflection\Property::OPTION_ASSOC)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined as association"
                        . " on entity " . $this->entityReflection->getClassName()
                        . "!"
                    );
                }

                $association = $property->getOption(Reflection\Property::OPTION_ASSOC);
                if ($association->isRemote()) {
                    $this->associations["remote"][$name] = $association;
                } else {
                    $this->associations["local"][$name] = $association;
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

                if (!$this->entityReflection->hasProperty($name)) {
                    throw new Exception\QueryException(
                        "Property '" . $name . "' is not defined on entity "
                        . $this->entityReflection->getClassName() . "!"
                    );
                }

                $property = $this->entityReflection->getProperty($name);
                if ($property->hasOption(Reflection\Property::OPTION_ASSOC)
                    || $property->hasOption(Reflection\Property::OPTION_COMPUTED)
                ) {
                    throw new Exception\QueryException(
                        "Associations and computed properties can not be selected!"
                    );
                }

                if (!array_search($name, $this->selection)) {
                    $this->selection[] = $name;
                }
            }
        }

        return $this;
    }

    protected function createSelection()
    {
        if (empty($this->selection)) {

            $selection = [];
            foreach ($this->entityReflection->getProperties() as $property) {

                // Exclude associations & computed properties
                if (!$property->hasOption(Reflection\Property::OPTION_ASSOC)
                    && !$property->hasOption(Reflection\Property::OPTION_COMPUTED)
                ) {
                    $selection[] = $property->getName(true);
                }
            }
        } else {

            $selection = $this->selection;

            // Add properties from filter
            Filter::merge(
                $this->entityReflection,
                [],
                $this->filter,
                function ($name) use ($selection) {
                    $selection[] = $name;
                }
            );

            // Include primary automatically if not provided
            if ($this->entityReflection->hasPrimary()) {

                $primaryName = $this->entityReflection
                    ->getPrimaryProperty()
                    ->getName();

                if (!in_array($primaryName, $selection)) {
                    $selection[] = $primaryName;
                }
            }

            // Unmap all names
            foreach ($selection as $index => $name) {
                $selection[$index] = $this->entityReflection->getProperty($name)->getName(true);
            }
        }

        // Add required keys from remote associations
        foreach ($this->associations["remote"] as $association) {

            if (($association instanceof Association\ManyToOne || $association instanceof Association\OneToOne)
                && !in_array($association->getReferencingKey(), $selection, true)
            ) {
                $selection[] = $association->getReferencingKey();
            }
        }

        return $selection;
    }

}