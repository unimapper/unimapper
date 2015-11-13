<?php

namespace UniMapper;

use UniMapper\Entity\Filter;
use UniMapper\Entity\Reflection;

class Mapper
{

    /** @var array */
    private $adapterMappings = [];

    public function registerAdapterMapping($name, Adapter\Mapping $mapping)
    {
        if (isset($this->adapterMappings[$name])) {
            throw new Exception\InvalidArgumentException(
                "Mapping on adapater " . $name . " already registered!"
            );
        }

        $this->adapterMappings[$name] = $mapping;
    }

    /**
     * Convert value to defined property format
     *
     * @param Entity\Reflection\Property $property
     * @param mixed                      $value
     *
     * @return mixed
     *
     * @throws Exception\InvalidArgumentException
     */
    public function mapValue(Entity\Reflection\Property $property, $value)
    {
        // Call adapter's mapping if needed
        if (!$property->getEntityReflection()->hasAdapter()) {
            throw new Exception\InvalidArgumentException(
                "Entity " . $property->getEntityReflection()->getClassName()
                . " has no adapter defined!"
            );
        }
        if (isset($this->adapterMappings[$property->getEntityReflection()->getAdapterName()])) {
            $value = $this->adapterMappings[$property->getEntityReflection()->getAdapterName()]
                ->mapValue($property, $value);
        }

        if ($property->hasOption(Reflection\Property\Option\Map::KEY)) {

            $mapOption = $property->getOption(Reflection\Property\Option\Map::KEY);
            if (!$mapOption) {
                throw new Exception\InvalidArgumentException(
                    "Mapping disabled on property " . $property->getName() . "!"
                );
            }

            // Call map filter from property option
            $filterIn = $mapOption->getFilterIn();
            if ($filterIn) {
                $value = call_user_func($filterIn, $value);
            }
        }

        if ($value === null || $value === "") {
            return null;
        }

        if ($property->isScalarType($property->getType())
            || $property->getType() === Entity\Reflection\Property::TYPE_ARRAY
        ) {
            // Scalar & array

            if ($property->getType() === Reflection\Property::TYPE_BOOLEAN
                && in_array($value, ["false", "true"], true)
            ) {
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            if (Validator::isTraversable($value)
                && $property->getType() !== Entity\Reflection\Property::TYPE_ARRAY
            ) {
                throw new Exception\InvalidArgumentException(
                    "Traversable value can not be mapped to scalar!",
                    $value
                );
            }

            if (settype($value, $property->getType())) {
                return $value;
            }
        } elseif ($property->getType() === Entity\Reflection\Property::TYPE_COLLECTION) {
            // Collection

            return $this->mapCollection($property->getTypeOption(), $value);
        } elseif ($property->getType() === Entity\Reflection\Property::TYPE_ENTITY) {
            // Entity

            return $this->mapEntity($property->getTypeOption(), $value);
        } elseif ($property->getType() === Entity\Reflection\Property::TYPE_DATETIME
            || $property->getType() === Entity\Reflection\Property::TYPE_DATE
        ) {
            // DateTime & Date

            if ($value instanceof \DateTime) {
                return $value;
            } elseif (is_array($value) && isset($value["date"])) {
                $date = $value["date"];
            } elseif (is_object($value) && isset($value->date)) {
                $date = $value->date;
            } else {
                $date = $value;
            }

            if (isset($date)) {

                try {
                    return new \DateTime($date);
                } catch (\Exception $e) {
                    throw new Exception\InvalidArgumentException(
                        "Can not map value to DateTime automatically! "
                        . $e->getMessage(),
                        $value
                    );
                }
            }
        }

        // Unexpected value type
        throw new Exception\InvalidArgumentException(
            "Value can not be mapped automatically!",
            $value
        );
    }

    public function mapCollection($name, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new Exception\InvalidArgumentException(
                "Input data must be traversable!",
                $data
            );
        }

        $collection = new Entity\Collection($name);
        foreach ($data as $value) {
            $collection[] = $this->mapEntity($name, $value);
        }
        return $collection;
    }

    public function mapEntity($name, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new Exception\InvalidArgumentException(
                "Input data must be traversable!",
                $data
            );
        }

        $reflection = Entity\Reflection::load($name);

        $values = [];
        foreach ($data as $name => $value) {

            // Map property name if needed
            foreach ($reflection->getProperties() as $property) {

                if ($property->hasOption(Reflection\Property\Option\Map::KEY)) {
                    // Option mapping

                    $option = $property->getOption(Reflection\Property\Option\Map::KEY);
                    if (!$option) {

                        if ($property->getName() === $name) {
                            continue 2;
                        }
                        continue; // Skip disabled
                    }

                    if ($option->getUnmapped() === $name) {

                        $name = $property->getName();
                        break;
                    }
                } else {
                    // Auto-mapping

                    if ($name === $property->getName()) {
                        break;
                    } else {
                        continue;
                    }
                }
            }

            // Skip undefined properties
            if (!$reflection->hasProperty($name)) {
                continue;
            }

            // Map value
            $values[$name] = $this->mapValue(
                $reflection->getProperty($name),
                $value
            );
        }

        return $reflection->createEntity($values);
    }

    /**
     * Convert entity to simple array
     *
     *  @param Entity $entity
     *
     *  @return array
     */
    public function unmapEntity(Entity $entity)
    {
        $output = [];
        foreach ($entity->getData() as $name => $value) {

            $property = $entity::getReflection()->getProperty($name);

            // Skip associations & readonly & disabled mapping
            if ($property->hasOption(Reflection\Property\Option\Assoc::KEY)
                || !$property->isWritable()
                || ($property->hasOption(Reflection\Property\Option\Map::KEY)
                    && !$property->getOption(Reflection\Property\Option\Map::KEY))
            ) {
                continue;
            }

            $output[$property->getUnmapped()] = $this->unmapValue($property, $value);
        }
        return $output;
    }

    public function unmapValue(Entity\Reflection\Property $property, $value)
    {
        if ($property->hasOption(Reflection\Property\Option\Map::KEY)) {

            $mapOption = $property->getOption(Reflection\Property\Option\Map::KEY);
            if (!$mapOption) {
                throw new Exception\InvalidArgumentException(
                    "Mapping disabled on property " . $property->getName() . "!"
                );
            }

            // Call map filter from property option
            $filterOut = $mapOption->getFilterOut();
            if ($filterOut) {
                $value = call_user_func($filterOut, $value);
            }
        }

        if ($value instanceof Entity\Collection) {
            return $this->unmapCollection($value);
        } elseif ($value instanceof Entity) {
            return $this->unmapEntity($value);
        }

        // Call adapter's mapping if needed
        if (!$property->getEntityReflection()->hasAdapter()) {
            throw new Exception\InvalidArgumentException(
                "Entity " . $property->getEntityReflection()->getClassName()
                . " has no adapter defined!"
            );
        }

        if (isset($this->adapterMappings[$property->getEntityReflection()->getAdapterName()])) {
            return $this->adapterMappings[$property->getEntityReflection()->getAdapterName()]
                ->unmapValue($property, $value);
        }

        return $value;
    }

    /**
     * Convert entity to simple array
     *
     *  @param Entity\Collection $collection
     *
     *  @return array
     */
    public function unmapCollection(Entity\Collection $collection)
    {
        $data = [];
        foreach ($collection as $index => $entity) {
            $data[$index] = $this->unmapEntity($entity);
        }
        return $data;
    }

    /**
     * @param Reflection $reflection
     * @param array      $filter
     *
     * @return array
     */
    public function unmapFilter(Reflection $reflection, array $filter)
    {
        $result = [];

        if (Filter::isGroup($filter)) {

            foreach ($filter as $modifier => $item) {
                $result[$modifier] = $this->unmapFilter($reflection, $item);
            }
        } else {

            foreach ($filter as $name => $item) {

                $property = $reflection->getProperty($name);
                $unmappedName = $property->getUnmapped();

                foreach ($item as $modifier => $value) {

                    $result[$unmappedName][$modifier] = $this->unmapValue(
                        $property,
                        $value
                    );
                }
            }
        }

        return $result;
    }

}