<?php

namespace UniMapper;

use UniMapper\Exceptions\MapperException,
    UniMapper\EntityCollection,
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

    public function createCollection($entityClass, $data, callable $valueCallback = null)
    {
        $collection = new EntityCollection($entityClass);
        $collection->importData($data, $this->name, $valueCallback);
        return $collection;
    }

    public function createEntity($entityClass, $data, callable $valueCallback = null)
    {
        $entity = new $entityClass;
        $entity->importData($data, $this->name, $valueCallback);
        return $entity;
    }

    /*
     * Convert entity to simple array
     *
     *  @param \UniMapper\Entity $entity Entity
     *
     *  @return array
     */
    public function entityToData(\UniMapper\Entity $entity)
    {
        return $entity->toArray(true, $this->name);
    }

}