<?php

namespace UniMapper;

use UniMapper\Mapper,
    UniMapper\Utils\Validator,
    UniMapper\EntityCollection,
    UniMapper\Reflection\EntityReflection,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\PropertyUndefinedException;

/**
 * Entity is ancestor for all entities and provides global methods, which
 * can be used in every new entity object.
 */
abstract class Entity implements \JsonSerializable
{

    protected $mappers = array();
    protected $reflection;
    private $data = array();

    public function __construct()
    {
        $this->reflection = new EntityReflection($this);
    }

    public static function create($values = null)
    {
        $class = get_called_class();
        $entity = new $class;

        if ($values !== null) {

            if (!Validator::isTraversable($values)) {
                throw new \Exception("Values must be traversable data!");
            }

            $properties = $entity->getReflection()->getProperties();
            foreach ($values as $propertyName => $value) {

                if (!isset($properties[$propertyName])) {
                    throw new \Exception("Property " . $propertyName . " does not exist in entity " . $class . "!");
                }

                try {
                    $entity->{$propertyName} = $value;
                } catch (PropertyTypeException $exception) {

                    if ($properties[$propertyName]->isBasicType()) {
                        if (settype($value, $properties[$propertyName]->getType())) {
                            $entity->{$propertyName} = $value;
                            continue;
                        }
                    }
                    throw new \Exception("Can not set value automatically!");
                }
            }
        }

        return $entity;
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

            $type = $properties[$name]->getType();
            if ($type instanceof EntityCollection) {
                return $type;
            }
            return null;
        }

        throw new PropertyUndefinedException(
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
     * @throws \UniMapper\Exceptions\PropertyUndefinedException
     */
    public function __set($name, $value)
    {
        $properties = $this->reflection->getProperties();
        if (!isset($properties[$name])) {
            throw new PropertyUndefinedException("Undefined property with name '" . $name . "'!", $this->reflection);
        }

        // @todo elaborate null values
        if ($value === null) {
            return;
        }

        // Validate value
        try {
            $properties[$name]->validateValue($value);
        } catch (PropertyTypeException $exception) {
            throw new PropertyTypeException($exception->getMessage(), $properties[$name]->getEntityReflection(), $properties[$name]->getRawDefinition());
        }

        // Set value
        $this->data[$name] = $value;
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
     * Get changed data only
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get entity values as array
     *
     * @param boolean $nesting Convert nested entities and collections too
     *
     * @return array
     */
    public function toArray($nesting = false)
    {
        $output = array();
        foreach ($this->reflection->getProperties() as $propertyName => $property) {

            $type = $property->getType();
            if (($type instanceof EntityCollection || $type instanceof Entity) && $nesting) {
                $output[$propertyName] = $this->{$propertyName}->toArray($nesting);
            } else {
                $output[$propertyName] = $this->{$propertyName};
            }
        }
        return $output;
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

}