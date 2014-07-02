<?php

namespace UniMapper;

use UniMapper\Exceptions\MapperException,
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

    /** @var \UniMapper\Cache */
    protected $cache;

    /** @var array  */
    private $customMappers;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setCache(Cache $cache)
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

    public function mapEntity($class, $data)
    {
        if (!Validator::validateTraversable($data)) {
            throw new MapperException("Input data must be traversable!");
        }

        if ($this->cache) {
            $reflection = $this->cache->loadEntityReflection($class);
        } else {
            $reflection = new Reflection\Entity($class);
        }

        $entity = $reflection->createEntity();

        $propertiesReflection = $reflection->getProperties();
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

            $property = $propertiesReflection[$propertyName];
            if ($property->hasCustomMapper('decode')){
                $entity->{$propertyName} = $this->decodeValue($entity, $property, $value);
            } else {
                $entity->{$propertyName} = $this->mapValue($property, $value);
            }

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
            $property = $entity->getReflection()->getProperties()[$propertyName];
            $output[$property->getMappedName()] = $this->unmapValue( $value, $entity, $property );
        }
        return $output;
    }

    protected function unmapValue($value, $entity = null, $property = null )
    {
        if ($value instanceof EntityCollection) {
            return $this->unmapCollection($value);
        } elseif ($value instanceof Entity) {
            return $this->unmapEntity($value);
        }

        return $this->encodeValue($entity, $property, $value);
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

    /**
     * @param string $name
     * @param mixed $mapper
     * @return $this
     */
    public function registerCustomMapper($name, $mapper)
    {
        $this->customMappers[$name] = $mapper;
        return $this;
    }

    /**
     * @param \UniMapper\Entity                            $entity
     * @param string|\UniMapper\Reflection\Entity\Property $property
     * @param mixed                                        $value
     *
     * @return mixed
     * @throws Exceptions\MapperException
     */
    protected function encodeValue($entity, $property, $value)
    {
        $property = is_scalar($property) ? $entity->getReflection()->getProperties()[$property] : $property;
        if ($property->hasCustomMapper('encode')) {
            $definition = $property->getCustomMapper('encode');
            $target = array_shift($definition);
            $methodName = isset($definition[0]) ? array_shift($definition) : 'encode' . ucfirst($property->getName());

            if (strtolower($target) === 'self') {
                $target = $entity;
            }
            else if (isset($this->customMappers[$target])) {
                $target = $this->customMappers[$target];
            }

            if ((is_object($target) || class_exists($target)) && method_exists($target, $methodName)) {
                return call_user_func_array(array($target, $methodName), array($value));
            }

            throw new MapperException("Unable to encode value! Target '$target'' or its method '$methodName' is not callable.");
        }
        return $value;
    }

    /**
     * @param \UniMapper\Entity                            $entity
     * @param string|\UniMapper\Reflection\Entity\Property $property
     * @param mixed                                        $value
     *
     * @throws Exceptions\MapperException
     * @return mixed
     */
    protected function decodeValue($entity, $property, $value)
    {
        $property = is_scalar($property) ? $entity->getReflection()->getProperties()[$property] : $property;
        if ($property->hasCustomMapper('decode')) {
            $definition = $property->getCustomMapper('decode');
            $target = array_shift($definition);
            $methodName = isset($definition[0]) ? array_shift($definition) : 'decode' . ucfirst($property->getName());

            if (strtolower($target) === 'self') {
                $target = $entity;
            }
            else if (isset($this->customMappers[$target])) {
                $target = $this->customMappers[$target];
            }

            if ((is_object($target) || class_exists($target)) && method_exists($target, $methodName)) {
                return call_user_func_array(array($target, $methodName), array($value));
            }

            throw new MapperException("Unable to decode value! Target '$target'' or its method '$methodName' is not callable.");
        }
        return $value;
    }

}
