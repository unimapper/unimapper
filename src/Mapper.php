<?php

namespace UniMapper;

use UniMapper\EntityCollection,
    UniMapper\Exceptions\MapperException,
    UniMapper\Utils\Property,
    UniMapper\Utils\Validator,
    UniMapper\Reflection\EntityReflection;

/**
 * Mapper is ancestor for every new mapper. It defines common methods or
 * parameters used in its descendants.  Mappers are generally used to
 * communicate between repository and data source.
 */
abstract class Mapper implements Mapper\IMapper
{

    protected $name;

    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    public function getName()
    {
        return $this->name;
    }

    final public function getResource(EntityReflection $entityReflection)
    {
        $mappers = $entityReflection->getMappers();
        if (!isset($mappers[$this->name])) {
            throw new MapperException("Entity does not define mapper with name " . $this->name . "!");
        }
        return $mappers[$this->name]->getResource();
    }

    protected function mapProperties(array $properties)
    {
        $output = array();
        foreach ($properties as $property) {

            if ($property->getMapping()) {

                $mapDefinition = $property->getMapping()->getName($this->name);
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
        $properties = $entity->getReflection()->getProperties($this->name);

        $output = array();
        foreach ($entity->toArray() as $name => $value) {

            if (!isset($properties[$name])) {
                continue;
            }

            // Property mapping definition required
            $mapping = $properties[$name]->getMapping();
            if ($mapping === false) {
                continue;
            }
            $mappedPropertyName = $mapping->getName($this->name);

            if ($value instanceof EntityCollection) {
                // Collection of entities
                foreach ($value as $subEntity) {
                    $output[$mappedPropertyName][] = $this->entityToData($subEntity);
                }
            } else {
                $output[$mappedPropertyName] = $value;
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
        $properties = $entityReflection->getProperties($this->name);
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
     * @param mixed                     $data
     * @param \UniMapper\Entity         $entityClass
     *
     * @return \UniMapper\EntityCollection
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function dataToCollection($data, $entityClass)
    {
        if (!Validator::isTraversable($data)) {
            throw new MapperException("Input data must be traversable!");
        }

        $collection = new EntityCollection($entityClass);
        foreach ($data as $key => $value) {

            $entity = $this->dataToEntity($value, new $entityClass);

            // Get primary property key if defined
            $primaryProperty = $entity->getReflection()->getPrimaryProperty();
            if ($primaryProperty !== null) {
                $primaryValue = $entity->{$primaryProperty->getName()};
                if ($primaryValue === null) {
                    throw new MapperException("Missing value in primary property " . $primaryProperty->getName() . "!");
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
     * @param mixed             $data
     * @param \UniMapper\Entity $entity
     *
     * @return \UniMapper\Entity
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function dataToEntity($data, \UniMapper\Entity $entity)
    {
        if (!Validator::isTraversable($data)) {
            throw new MapperException("Input data must be traversable!");
        }

        $properties = $entity->getReflection()->getProperties();
        foreach ($data as $name => $value) {

            $property = null;

            // Try to find property mapping first
            foreach ($properties as $item) {
                $mapping = $item->getMapping();
                if ($mapping && $mapping->getName($this->name) === $name) {
                    $property = $item;
                    $name = $property->getName();
                    break;
                }
            }

            // If not found then check if property exists
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
            $entity->{$name} = $this->convertPropertyValue($property, $value);
        }
        return $entity;
    }

    /**
     * Convert value to defined property format
     *
     * @param \UniMapper\Utils\Property $property Property
     * @param mixed                     $value    Value
     *
     * @return mixed
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function convertPropertyValue(Property $property, $value)
    {
        $type = $property->getType();

        if (in_array($type, $property->getBasicTypes())) {
            // Basic type

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
            }

            throw new MapperException(
                "Can not convert value to entity @property"
                . " \${$property->getName()}. Expected {$type} but "
                . "conversion of " . gettype($value) . " failed!"
            );

        } elseif ($type instanceof EntityCollection) {
            // Entity collection

            $entityClass = $type->getEntityClass();
            foreach ($value as $item) {
                $type[] = $this->dataToEntity(
                    $item,
                    new $entityClass
                );
            }
            return $type;

        } elseif ($value instanceof \stdClass
            && isset($value->date)
            && isset($value->timezone_type)
            && isset($value->timezone)
        ) {
            // Datetime in stdClass

            return new \DateTime($value->date);

        } elseif (class_exists($type)) {

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