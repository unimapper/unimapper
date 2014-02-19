<?php

namespace UniMapper;

use UniMapper\EntityCollection,
    UniMapper\Exceptions\MapperException,
    UniMapper\Utils\Property,
    UniMapper\Reflection\EntityReflection,
    UniMapper\Utils\AnnotationParser;

/**
 * Mapper is ancestor for every new mapper. It defines common methods or
 * parameters used in its descendants.  Mappers are generally used to
 * communicate between repository and database abstract layer or php database
 * class itself.
 */
abstract class Mapper implements Mapper\IMapper
{

    /**
     * Return class name when it is treated like a string
     *
     * @return string
     */
    public function __toString()
    {
        return get_called_class();
    }

    final public function getResource(EntityReflection $entityReflection)
    {
        $mappers = $entityReflection->getMappers();
        return $mappers[(string) $this]->getResource();
    }

    protected function mapProperties(array $properties)
    {
        $output = array();
        foreach ($properties as $property) {

            if ($property->getMapping()) {

                $mapDefinition = $property->getMapping()->getName((string) $this);
                if ($mapDefinition) {
                    $output[] = $mapDefinition;
                    continue;
                }
            }
            $output[] = $property->getName();
        }
        return $output;
    }

    /**
     * Convert entity to simple array
     *
     *  @param \UniMapper\Entity $entity Entity
     *
     *  @return array
     */
    public function entityToData(\UniMapper\Entity $entity)
    {
        $properties = AnnotationParser::getEntityProperties(get_class($entity));

        $output = array();
        foreach ($entity as $name => $value) {

            if (!isset($properties[$name])) {
                continue;
            }

            // Property mapping definition required
            $mapping = $properties[$name]->getMapping()->getName((string) $this);
            if ($mapping === false) {
                continue;
            }

            if ($value instanceof EntityCollection) {
                // Collection of entities
                foreach ($value as $subEntity) {
                    $output[$mapping][] = $this->entityToData($subEntity);
                }
            } else {
                $output[$mapping] = $value;
            }
        }
        return $output;
    }

    /**
     * Get selection
     *
     * @param \UniMapper\Reflection\EntityReflection $entityReflection Entity reflection
     * @param string                                 $selection        Required selection
     *
     * @return array
     */
    protected function getSelection(EntityReflection $entityReflection, array $selection = array())
    {
        $properties = $entityReflection->getProperties((string) $this);
        if (count($selection) === 0) {
            return $this->mapProperties($properties);
        }

        // Add primary property automatically if not set
        $primaryPropertyName = $entityReflection->getPrimaryProperty()->getName();
        if (!in_array($primaryPropertyName, $selection)) {
            $selection[] = $primaryPropertyName;
        }

        $result = array();
        foreach ($selection as $propertyName) {

            if (isset($properties[$propertyName])) {
                $result[$propertyName] = $properties[$propertyName];
            }
        }
        return $this->mapProperties($result);
    }

    /**
     * Convert data to entity collection
     *
     * @param mixed                   $data        Input data to convert,
     *                                             expected iterable structure.
     * @param \UniMapper\Entity         $entityClass Entity
     * @param \UniMapper\Utils\Property $property    Property
     *
     * @return \UniMapper\EntityCollection
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function dataToCollection($data, $entityClass, Property $primaryProperty = null)
    {
        if (!is_array($data) && $data instanceof \Traversable) {
            throw new MapperException(
                "Input data must be array or implement an iterator!"
            );
        }

        $collection = new EntityCollection($entityClass);

        foreach ($data as $key => $value) {
            $entity = $this->dataToEntity($value, new $entityClass);
            if ($primaryProperty !== null) {
                $primaryValue = $entity->{$primaryProperty->getName()};
                if ($primaryValue === NULL) {
                    throw new MapperException("Missing primary property " . $primaryProperty->getName() . "!");
                }
                $key = $primaryValue;
            }
            $collection[$key] = $entity;
        }

        return $collection;
    }

    /**
     * Map data to defined entity
     *
     * @param mixed           $data   Input data to convert
     * @param \UniMapper\Entity $entity Entity
     *
     * @return \UniMapper\Entity
     */
    public function dataToEntity($data, \UniMapper\Entity $entity)
    {
        $properties = AnnotationParser::getEntityProperties(get_class($entity));
        foreach ($data as $name => $value) {

            $property = null;

            // Find property in mapping first
            foreach ($properties as $item) {
                $mapping = $item->getMapping();
                if ($mapping && $mapping->getName((string) $this) === $name) {
                    $property = $item;
                    $name = $property->getName();
                    break;
                }
            }

            // If not found then check if property exist
            if ($property === null && isset($properties[$name])) {
                $property = $properties[$name];
            }

            // Skip if property not found
            if ($property === null) {
                continue;
            }

            // Apply custom value modifier from mapper
            $value = $this->modifyResultValue($value);

            // Convert value to defined type in entity property
            $entity->{$name} = $this->mapToValue($property, $value);
        }
        return $entity;
    }

    /**
     * Convert value to defined entity property format
     *
     * @param \UniMapper\Utils\Property $property Property
     * @param mixed                   $value    Value
     *
     * @return mixed
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    protected function mapToValue(Property $property, $value)
    {
        $type = $property->getType();

        if (in_array($type, $property->getBasicTypes())) {
            // Basic types
            if ($type === "boolean" && $value === "false") {
                return false; // covers string "false" of flexibee response @todo move out
            }

            if ($type === "boolean" && $value === "true") {
                return true;  // covers string "true" of flexibee response @todo move out
            }

            if ($value === null) {
                return null;
            }

            if (settype($value, $type)) {
                return $value;
            } else {
                throw new MapperException(
                    "Can not convert value to entity @property"
                    . " \${$property->getName()}. Expected {$type} but "
                    . "conversion of " . gettype($value) . " failed!"
                );
            }
        }

        if ($type instanceof EntityCollection) {
            // Entity collection
            $entityClass = $type->getEntityClass();
            foreach ($value as $item) {
                $type[] = $this->dataToEntity(
                    $item,
                    new $entityClass
                );
            }
            return $type;
        }

        if ($value instanceof \stdClass
            && isset($value->date)
            && isset($value->timezone_type)
            && isset($value->timezone)
        ) {
            // Datetime in stdClass
            return new \DateTime($value->date);
        }

        if (class_exists($type)) {
            if ($value instanceof $type) {
                // Expected object already given
                return $value;
            }
            if ($type instanceof \UniMapper\Entity) {
                // Entity
                return $this->dataToEntity($value, new $type);
            }
        }

        // Unexpected value type
        throw new MapperException(
            "Unexpected value type given. Can not convert value to entity "
            . "@property \$" . $property->getName() . ". Expected " . $type
            . " but " . gettype($value) . " given!"
        );
    }

    /**
     * Modify result value eg. convert DibiDateTime do Datetime etc.
     *
     * @param mixed $value Value
     *
     * @return mixed
     */
    protected function modifyResultValue($value)
    {
        return $value;
    }

}