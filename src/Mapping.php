<?php

namespace UniMapper;

use UniMapper\Entity,
    UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection;

class Mapping
{

    /**
     * Convert value to defined property format
     *
     * @param \UniMapper\Reflection\Entity\Property $property
     * @param mixed                                 $value
     *
     * @return mixed
     *
     * @throws Exception\MappingException
     */
    public function mapValue(Reflection\Entity\Property $property, $value)
    {
        // Apply map filter first
        if ($property->getMapping() && $property->getMapping()->getFilterIn()) {
            $value = call_user_func($property->getMapping()->getFilterIn(), $value);
        }

        $type = $property->getType();

        if ($value === null || $value === "") {
            return null;
        }

        if ($property->isTypeBasic()) {
            // Basic type

            if ($type === "boolean" && $value === "false") {
                return false;
            }

            if ($type === "boolean" && $value === "true") {
                return true;
            }

            if (settype($value, $type)) {
                return $value;
            }

        } elseif ($type instanceof EntityCollection) {
            // Collection

            return $this->mapCollection($type->getEntityReflection(), $value);
        } elseif ($type instanceof Reflection\Entity) {
            // Entity

            return $this->mapEntity($type, $value);
        } elseif ($type === Reflection\Entity\Property::TYPE_DATETIME) {
            // DateTime

            try {
                return new \DateTime($value);
            } catch (\Exception $e) {
                throw new MappingException(
                    "Can not map value to DateTime automatically! "
                    . $e->getMessage()
                );
            }
        }

        // Unexpected value type
        throw new MappingException(
            "Unexpected value type given. Can not convert value to entity "
            . "@property $" . $property->getName() . ". Expected " . $type
            . " but " . gettype($value) . " given!"
        );
    }

    public function mapCollection(Reflection\Entity $entityReflection, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new Exception\InvalidArgumentException(
                "Input data must be traversable!"
            );
        }

        $collection = new EntityCollection($entityReflection);
        foreach ($data as $value) {
            $collection[] = $this->mapEntity($entityReflection, $value);
        }
        return $collection;
    }

    public function mapEntity(Reflection\Entity $entityReflection, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new MappingException("Input data must be traversable!");
        }

        $values = [];
        foreach ($data as $index => $value) {

            $propertyName = $index;

            // Map property name if needed
            foreach ($entityReflection->getProperties() as $propertyReflection) {

                if ($propertyReflection->getMappedName() === $index) {

                    $propertyName = $propertyReflection->getName();
                    break;
                }
            }

            // Skip undefined properties
            if (!$entityReflection->hasProperty($propertyName)) {
                continue;
            }

            // Map value
            $values[$propertyName] = $this->mapValue(
                $entityReflection->getProperty($propertyName),
                $value
            );
        }

        return $entityReflection->createEntity($values);
    }

    /**
     * Convert entity to simple array
     *
     *  @param \UniMapper\Entity $entity Entity
     *
     *  @return array
     */
    public function unmapEntity(\UniMapper\Entity $entity)
    {
        $output = [];
        foreach ($entity->getData() as $propertyName => $value) {

            $property = $entity->getReflection()->getProperties()[$propertyName];
            $output[$property->getMappedName()] = $this->unmapValue(
                $property,
                $value
            );
        }
        return $output;
    }

    public function unmapValue(Reflection\Entity\Property $property, $value)
    {
        // Apply map filter first
        if ($property->getMapping() && $property->getMapping()->getFilterOut()) {
            $value = call_user_func($property->getMapping()->getFilterOut(), $value);
        }

        if ($value instanceof EntityCollection) {
            return $this->unmapCollection($value);
        } elseif ($value instanceof Entity) {
            return $this->unmapEntity($value);
        }

        return $value;
    }

    /*
     * Convert entity to simple array
     *
     *  @param \UniMapper\EntityCollection $collection Entity collection
     *
     *  @return array
     */
    public function unmapCollection(EntityCollection $collection)
    {
        $data = array();
        foreach ($collection as $index => $entity) {
            $data[$index] = $this->unmapEntity($entity);
        }
        return $data;
    }

    public function unmapSelection(Reflection\Entity $entityReflection,
        array $selection
    ) {
        if (count($selection) === 0) {
            // Select all if not set

            $selection = array_keys($entityReflection->getProperties());
        } else {
            // Add primary property automatically if not set in selection

            $primaryPropertyName = $entityReflection->getPrimaryProperty()
                ->getName();

            if (!in_array($primaryPropertyName, $selection)) {
                $selection[] = $primaryPropertyName;
            }
        }

        $result = [];
        foreach ($selection as $name) {

            if ($entityReflection->hasProperty($name)) {

                $property = $entityReflection->getProperty($name);

                // Skip associations and computed properties
                if ($property->isComputed() || $property->isAssociation()) {
                    continue;
                }

                $result[] = $property->getMappedName();
            }
        }
        return $result;
    }

    public function unmapOrderBy(Reflection\Entity $entityReflection,
        array $items
    ) {
        $unmapped = [];
        foreach ($items as $name => $direction) {
            $mappedName = $entityReflection->getProperties()[$name]->getMappedName();
            $unmapped[$mappedName] = $direction;
        }
        return $unmapped;
    }

    public function unmapConditions(Reflection\Entity $entityReflection,
        array $conditions
    ) {
        foreach ($conditions as $condition) {

            if (is_array($condition[0])) {

                $condition[0] = $this->unmapConditions(
                    $entityReflection,
                    $condition[0]
                );
            } else {
                $condition[0] = $entityReflection->getProperty($condition[0])
                    ->getMappedName();
            }
        }
        return $conditions;
    }

}