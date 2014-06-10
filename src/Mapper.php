<?php

namespace UniMapper;

use UniMapper\Exceptions\MapperException,
    UniMapper\Cache\ICache,
    UniMapper\Entity,
    UniMapper\EntityCollection,
    UniMapper\Validator,
    UniMapper\Reflection;

/**
 * Mapper is generally used to communicate between repository and data source.
 */
abstract class Mapper implements Mapper\IMapper
{

    /** @var string */
    protected $name;

    /** @var \UniMapper\Cache\ICache */
    protected $cache;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setCache(ICache $cache)
    {
        $this->cache = $cache;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Convert value to defined property format
     *
     * @param \UniMapper\Reflection\Entity\Property $property
     * @param string                                $value
     *
     * @return mixed
     *
     * @throws \UniMapper\Exceptions\MapperException
     */
    public function mapValue(Reflection\Entity\Property $property, $value)
    {
        $type = $property->getType();

        if ($value === null || $value === "") {
            return null;
        }

        if ($property->isBasicType()) {
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

            throw new MapperException(
                "Can not convert value to entity @property"
                . " $" . $property->getName() . ". Expected " . $type . " but "
                . "conversion of " . gettype($value) . " failed!"
            );

        } elseif ($type instanceof EntityCollection) {

            return $this->mapCollection($type->getEntityClass(), $value);

        } elseif (class_exists($type)) {

            if ($value instanceof $type) {
                // Expected object already given
                return $value;
            } elseif ($type instanceof Entity) {
                // Entity
                return $this->mapEntity(get_class($type), $value);
            } elseif ($type === "DateTime") {
                // DateTime
                try {
                    return new \DateTime($value);
                } catch (\Exception $e) {
                    throw new MapperException("Can not map value to DateTime automatically! " . $e->getMessage());
                }
            }
        }

        // Unexpected value type
        throw new MapperException(
            "Unexpected value type given. Can not convert value to entity "
            . "@property $" . $property->getName() . ". Expected " . $type
            . " but " . gettype($value) . " given!"
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
        $output = [];
        foreach ($entity->getData() as $propertyName => $value) {

            $mappedName = $entity->getReflection()->getProperties()[$propertyName]->getMappedName();
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