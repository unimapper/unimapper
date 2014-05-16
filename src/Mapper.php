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
        $mapperReflection = $entityReflection->getMapperReflection();
        if ($mapperReflection->getName() !== $this->name) {
            throw new MapperException("Entity does not define mapper with name " . $this->name . "!");
        }
        return $mapperReflection->getResource();
    }

    protected function mapProperties(array $propertyReflections)
    {
        $output = array();
        foreach ($propertyReflections as $reflection) {
            $output[] = $reflection->getMappedName();
        }
        return $output;
    }

    protected function getSelection(Reflection\Entity $entityReflection, array $selection = array())
    {
        $propertyReflections = $entityReflection->getProperties();
        if (count($selection) === 0) {
            return $this->mapProperties($propertyReflections);
        }

        $result = array();
        foreach ($selection as $propertyName) {

            if (isset($propertyReflections[$propertyName])) {
                $result[$propertyName] = $propertyReflections[$propertyName];
            }
        }
        return $this->mapProperties($result);
    }

    /**
     * Translate conditions
     */
    protected function translateConditions(Reflection\Entity $entityReflection, array $conditions)
    {
        $propertyReflections = $entityReflection->getProperties();

        $result = array();
        foreach ($conditions as $condition) {

            if (is_array($condition[0])) {
                // Nested conditions

                list($nestedConditions, $joiner) = $condition;
                $condition[0] = $this->translateConditions($entityReflection, $nestedConditions);

                // Skip empty conditions
                if (empty($condition[0])) {
                    continue;
                }
            } else {
                // Simple condition
                $condition[0] = $propertyReflections[$condition[0]]->getMappedName();
            }

            $result[] = $condition;
        }

        return $result;
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
    protected function mapValue(Reflection\Entity\Property $property, $data)
    {
        $type = $property->getType();

        if ($data === null || $data === "") {
            return null;
        }

        if ($property->isBasicType()) {
            // Basic type

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

            return $this->mapCollection($type->getEntityClass(), $data);

        } elseif (class_exists($type)) {

            if ($data instanceof $type) {
                // Expected object already given
                return $data;
            } elseif ($type instanceof Entity) {
                // Entity
                return $this->mapEntity(get_class($type), $data);
            } elseif ($type === "DateTime") {
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

    public function mapCollection($entityClass, $data)
    {
        if (!Validator::validateTraversable($data)) {
            throw new \Exception("Input data must be traversable!");
        }

        $collection = new EntityCollection($entityClass);
        foreach ($data as $value) {
            $collection[] = $this->mapEntity($entityClass, $value);
        }
        return $collection;
    }

    public function mapEntity($entityClass, $data)
    {
        if (!Validator::validateTraversable($data)) {
            throw new MapperException("Input data must be traversable!");
        }

        $entity = new $entityClass($this->cache);

        $propertiesReflection = $entity->getReflection()->getProperties();
        foreach ($data as $index => $value) {

            $propertyName = $index;

            // Mapping
            foreach ($propertiesReflection as $propertyReflection) {

                if ($propertyReflection->getMappedName() === $index) {

                    $propertyName = $propertyReflection->getName();
                    break;
                }
            }

            if (!isset($propertiesReflection[$propertyName])) {
                continue;
            }

            $entity->{$propertyName} = $this->mapValue($propertiesReflection[$propertyName], $value);
        }

        return $entity;
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
        $properties = $entity->getReflection()->getProperties();

        $output = [];
        foreach ($entity->getData() as $propertyName => $value) {

            $mappedName = $properties[$propertyName]->getMappedName();
            $output[$mappedName] = $this->unmapValue($value);
        }
        return $output;
    }

    protected function unmapValue($value)
    {
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
    public function unmapCollection(\UniMapper\EntityCollection $collection)
    {
        $data = array();
        foreach ($collection as $index => $entity) {
            $data[$index] = $this->unmapEntity($entity);
        }
        return $data;
    }

}