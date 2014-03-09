<?php

namespace UniMapper;

use UniMapper\Mapper,
    UniMapper\Utils\Validator,
    UniMapper\EntityCollection,
    UniMapper\Reflection\EntityReflection,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\PropertyAccessException;

/**
 * Entity is ancestor for all entities and provides global methods, which
 * can be used in every new entity object.
 */
abstract class Entity implements \JsonSerializable
{

    protected $mappers = array();
    protected $reflection;
    private $data = array();

    public function __construct(Mapper $mapper = null, $defaults = null)
    {
        if ($mapper) {
            $this->addMapper($mapper);
        }
        $this->reflection = new EntityReflection($this);
        if ($defaults !== null) {
            $this->importData($defaults);
        }
    }

    public function addMapper(Mapper $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;
    }

    public function isActive()
    {
        return count($this->mappers) > 0;
    }

    public function getMappers()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }
        return $this->mappers;
    }

    /**
     * Get property value
     *
     * @param string $name Property name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        $properties = $this->reflection->getProperties();
        if (isset($properties[$name])) {
            return null;
        }

        throw new PropertyAccessException(
            "Undefined property with name '" . $name . "'!",
            $this->reflection
        );
    }

    /**
     * Set property value
     *
     * @param string $name  Property name
     * @param mixed  $value Property value
     *
     * @throws \UniMapper\Exceptions\PropertyAccessException
     */
    public function __set($name, $value)
    {
        $properties = $this->reflection->getProperties();
        if (!isset($properties[$name])) {
            throw new PropertyAccessException(
                "Undefined property with name '" . $name . "'!",
                $this->reflection
            );
        }

        // Validate value
        try {
            $properties[$name]->validateValue($value);
        } catch (PropertyTypeException $exception) {
            throw new PropertyAccessException(
                $exception->getMessage(),
                $properties[$name]->getEntityReflection(),
                $properties[$name]->getRawDefinition()
            );
        }

        // Set value
        if ($properties[$name]->getType() instanceof EntityCollection
            && gettype($value) === "array"
        ) {
            $collection = $properties[$name]->getType();
            foreach ($value as $key => $item) {
                $collection[$key] = $item;
            }
            $this->data[$name] = $collection;
        } else {
            $this->data[$name] = $value;
        }
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    public function getReflection()
    {
        return $this->reflection;
    }

    /**
     * Get entity values as array
     *
     * @param boolean $nesting    Convert nested entities and collections too
     * @param string  $mapperName Map properties automatically
     *
     * @return array
     */
    public function toArray($nesting = false, $mapperName = null)
    {
        if ($mapperName !== null) {
            $properties = $this->reflection->getProperties($mapperName);
        }

        $output = array();
        foreach ($this->data as $propertyName => $value) {

            if ($mapperName !== null) {

                // Property mapping definition required
                $mapping = $properties[$propertyName]->getMapping();
                if ($mapping === false) {
                    continue;
                }
                $propertyName = $mapping->getName($mapperName);
            }

            if (($value instanceof EntityCollection || $value instanceof Entity) && $nesting) {
                $output[$propertyName] = $value->toArray($nesting, $mapperName);
            } else {
                $output[$propertyName] = $value;
            }
        }
        return $output;
    }

    /**
     * Load entity from sourceData
     *
     * @param \UniMapper\Entity $sourceData  Source data (entity) for load
     * @param array           $itemsToLoad If null, properties from annotation will be loaded
     */
    public function loadFromSource($sourceData, $itemsToLoad = null)
    {
        if ($itemsToLoad === null) {
            $itemsToLoad = array_keys($this->reflection->getProperties());
        }

        foreach ($itemsToLoad as $item) {
            if (isset($sourceData->{$item})) {
                $this->data[$item] = $sourceData->{$item};
            }
        }
    }

    /**

     * Convert to json representation of entity collection
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray(true);
    }

    /**
     * Merge entity
     *
     * @param \UniMapper\Entity $entity
     *
     * @return \UniMapper\Entity
     */
    public function merge(\UniMapper\Entity $entity)
    {
        $entityClass = get_called_class();
        if (!$entity instanceof $entityClass) {
            throw \Exception("Merged entity must be instance of " . $entityClass . "!");
        }

        foreach ($entity as $name => $value) {
            if (!isset($this->data[$name])) {
                $this->data[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * Save entity
     *
     * @return void
     */
    public function save()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity must have attached mappers!");
        }

        return new Query\Insert($this, $this->mappers);
    }

    public function delete()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity must have attached mappers!");
        }

        $primaryProperty = $this->reflection->getPrimaryProperty();
        if (!$primaryProperty) {
            throw new \Exception("No primary property defined!");
        }

        $query = new Query\Delete($this, $this->mappers);
        $query->where($primaryProperty->getName(), "=", $this->data[$primaryProperty->getName()]);
        $query->limit(1);
        return $query->execute();
    }

    /**
     * Map data to defined entity
     *
     * @param mixed    $data          Traversable data
     * @param string   $mapperName    Import only mapper data
     * @param callable $valueCallback Callback when converting data
     *
     * @return \UniMapper\Entity
     *
     * @throws \Exception
     */
    public function importData($data, $mapperName = null, callable $valueCallback = null)
    {
        if (!Validator::isTraversable($data)) {
            throw new \Exception("Input data must be traversable!");
        }

        $properties = $this->getReflection()->getProperties($mapperName);
        foreach ($data as $propertyName => $value) {

            // Mapping
            if ($mapperName !== null) {

                foreach ($properties as $property) {

                    $mapping = $property->getMapping();
                    if ($mapping && $mapping->getName($mapperName) === $propertyName) {

                        $propertyName = $property->getName();
                        break;
                    }
                }
            }

            if (!isset($properties[$propertyName])) {
                continue;
            }

            if ($valueCallback !== null) {
                $value = $valueCallback($value);
            }

            $this->{$propertyName} = $properties[$propertyName]->convertValue($value, $mapperName, $valueCallback);
        }
        return $this;
    }

}