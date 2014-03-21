<?php

namespace UniMapper;

use UniMapper\Exceptions\MapperException,
    UniMapper\Cache\ICache,
    UniMapper\Entity,
    UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection;

/**
 * Mapper is ancestor for every new mapper. It defines common methods or
 * parameters used in its descendants.  Mappers are generally used to
 * communicate between repository and data source.
 */
abstract class Mapper implements Mapper\IMapper
{

    protected $name;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    public function setCache(ICache $cache)
    {
        $this->cache = $cache;
    }

    public function getName()
    {
        return $this->name;
    }

    final public function getResource(Reflection\Entity $entityReflection)
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

    protected function getSelection(Reflection\Entity $entityReflection, array $selection = array())
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
     * Convert value to defined property format
     *
     * @param \UniMapper\Reflection\Entity\Property $property      Property reflection
     * @param string                                $data          Input data
     *
     * @return mixed
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function createValue(Reflection\Entity\Property $property, $data)
    {
        $data = $this->beforeCreateValue($data);

        $type = $property->getType();

        if ($property->isBasicType()) {
            // Basic type

            if ($data === null || $data === "") {
                return null;
            }

            if ($type === "boolean" && $data === "false") {
                return false;
            }

            if ($type === "boolean" && $data === "true") {
                return true;
            }

            if (settype($data, $type)) {
                return $data;
            }

            throw new MapperException(
                "Can not convert value to entity @property"
                . " $" . $property->getName() . ". Expected " . $type . " but "
                . "conversion of " . gettype($data) . " failed!"
            );

        } elseif ($type instanceof EntityCollection) {

            return $this->createCollection($type->getEntityClass(), $data);

        } elseif (class_exists($type)) {

            if ($data instanceof $type) {
                // Expected object already given
                return $data;
            } elseif ($type instanceof Entity) {
                // Entity
                return $this->createEntity(get_class($type), $data);
            } elseif ($type instanceof \DateTime) {
                // DateTime
                try {
                    return new \DateTime($data);
                } catch (\Exception $e) {
                    throw new MapperException("Can not map value to DateTime automatically! " . $e->getMessage());
                }
            }
        }

        // Unexpected value type
        throw new MapperException(
            "Unexpected value type given. Can not convert value to entity "
            . "@property $" . $property->getName() . ". Expected " . $type
            . " but " . gettype($data) . " given!"
        );
    }

    public function createCollection($entityClass, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new \Exception("Input data must be traversable!");
        }

        $collection = new EntityCollection($entityClass);
        foreach ($data as $value) {
            $collection[] = $this->createEntity($entityClass, $value);
        }
        return $collection;
    }

    public function createEntity($entityClass, $data)
    {
        if (!Validator::isTraversable($data)) {
            throw new MapperException("Input data must be traversable!");
        }

        $entity = new $entityClass($this->cache);

        $properties = $entity->getReflection()->getProperties($this->name);
        foreach ($data as $propertyName => $value) {

            // Mapping
            foreach ($properties as $property) {

                $mapping = $property->getMapping();
                if ($mapping && $mapping->getName($this->name) === $propertyName) {

                    $propertyName = $property->getName();
                    break;
                }
            }

            if (!isset($properties[$propertyName])) {
                continue;
            }

            $entity->{$propertyName} = $this->createValue($properties[$propertyName], $value);
        }

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
        $properties = $entity->getReflection()->getProperties($this->name);

        $output = array();
        foreach ($entity->getData() as $propertyName => $value) {

            // Skip properties unrelated to this mapper
            if (!isset($properties[$propertyName])) {
                continue;
            }

            // Property mapping definition required
            $mapping = $properties[$propertyName]->getMapping();
            if ($mapping === false) {
                continue;
            }
            $propertyName = $mapping->getName($this->name);

            if ($value instanceof EntityCollection) {
                $output[$propertyName] = $this->collectionToData($value);
            } elseif ($value instanceof Entity) {
                $output[$propertyName] = $this->entityToData($value);
            } else {
                $output[$propertyName] = $value;
            }
        }
        return $output;
    }

    /*
     * Convert entity to simple array
     *
     *  @param \UniMapper\EntityCollection $collection Entity collection
     *
     *  @return array
     */
    public function collectionToData(\UniMapper\EntityCollection $collection)
    {
        $data = array();
        foreach ($collection as $index => $entity) {
            $data[$index] = $this->entityToData($entity);
        }
        return $data;
    }

    protected function beforeCreateValue($value)
    {
        return $value;
    }

}