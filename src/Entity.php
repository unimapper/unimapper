<?php

namespace UniMapper;

use UniMapper\Validator,
    UniMapper\EntityCollection,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Cache\ICache,
    UniMapper\Exceptions\PropertyException,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\PropertyUndefinedException;

/**
 * Entity is ancestor for all entities and provides global methods, which
 * can be used in every new entity object.
 */
abstract class Entity implements \JsonSerializable, \Serializable
{

    private $reflection;
    private $data = array();
    private $mappers = array();

    /** @var \UniMapper\Cache\ICache $cache */
    private $cache;

    public function __construct(ICache $cache = null)
    {
        $className = get_called_class();

        if ($cache) {

            $key = "entity-" . $className;
            $this->reflection = $cache->load($key);
            if (!$this->reflection) {
                $this->reflection = new Reflection\Entity($className);
                $cache->save($key, $this->reflection, $this->reflection->getFileName());
            }
        } else {
            $this->reflection = new Reflection\Entity($className);
        }
    }

    public static function validateUrl($value)
    {
        return Validator::isUrl($value);
    }

    public static function validateEmail($value)
    {
        return Validator::isEmail($value);
    }

    public static function validateIp($value)
    {
        return Validator::isIp($value);
    }

    public static function validateIpv4($value)
    {
        return Validator::isIpv4($value);
    }

    public static function validateIpv6($value)
    {
        return Validator::isIpv6($value);
    }

    public function serialize()
    {
        return serialize($this->data);
    }

    public function unserialize($data)
    {
        $this->data = unserialize($data);
    }

    public function isActive()
    {
        return count($this->mappers) > 0;
    }

    public function setActive(array $mappers)
    {
        if ($this->isActive()) {
            throw new \Exception("Entity is already active!");
        }
        $this->mappers = $mappers;
    }

    public function getMappers()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }
        return $this->mappers;
    }

    public function save()
    {
        if (!$this->isActive()) {
            \Exception("Entity is not active!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = $this->{$primaryName};

        $data = $this->data;
        if ($primaryValue === null) {
            // Insert
            $query = new Query\Insert($this->reflection, $this->mappers, $data);
        } else {
            // Update
            unset($data[$primaryName]); // Changing primary value forbidden
            $query = new Query\Update($this->reflection, $this->mappers, $data);
        }

        $query->execute();
    }

    public function delete()
    {
        if (!$this->isActive()) {
            \Exception("Entity is not active!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = $this->{$primaryName};
        if ($primaryValue === null) {
            throw new \Exception("Primary value not set!");
        }

        $query = new Query\Delete($this->reflection, $this->mappers);
        $query->where($primaryName, "=", $primaryValue)->execute();
    }

    /**
     * Import and try to convert values automatically if possible
     *
     * @param mixed $values Traversable structure (array/object)
     */
    public function import($values)
    {
        if ($values !== null) {

            if (!Validator::isTraversable($values)) {
                throw new \Exception("Values must be traversable data!");
            }

            $properties = $this->reflection->getProperties();
            foreach ($values as $propertyName => $value) {

                if (!isset($properties[$propertyName])) {
                    throw new \Exception("Property " . $propertyName . " does not exist in entity " . get_called_class() . "!");
                }

                try {
                    $this->{$propertyName} = $value;
                } catch (PropertyTypeException $exception) {

                    $property = $properties[$propertyName];
                    $propertyType = $property->getType();

                    if ($property->isBasicType()) {
                        // Basic

                        if (settype($value, $propertyType)) {
                            $this->{$propertyName} = $value;
                            continue;
                        }
                    } elseif ($propertyType === "DateTime") {
                        // DateTime

                        $date = $value;
                        if (Validator::isTraversable($value)) {
                            if (isset($value["date"])) {
                                $date = $value["date"];
                            }
                        }
                        try {
                            $date = new \DateTime($date);
                        } catch (\Exception $e) {

                        }
                        if ($date instanceof \DateTime) {
                            $this->{$propertyName} = $date;
                            continue;
                        }
                    } elseif ($propertyType instanceof EntityCollection && Validator::isTraversable($value)) {
                        // Collection

                        $entityClass = $propertyType->getEntityClass();
                        $collection = new EntityCollection($entityClass);
                        foreach ($value as $index => $data) {
                            $collection[$index] = new $entityClass($this->cache);
                            $collection[$index]->import($data);
                        }
                        $this->{$propertyName} = $collection;
                        continue;
                    }

                    throw new \Exception("Can not set value on property '" . $propertyName . "' automatically!");
                }
            }
        }
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

            // computed property
            if ($properties[$name]->isComputed()) {

                $computedValue = $this->{$properties[$name]->getComputedMethodName()}();
                if ($computedValue === null) {
                    return null;
                }
                $properties[$name]->validateValue($computedValue);
                return $computedValue;
            }

            // empty collection
            $type = $properties[$name]->getType();
            if ($type instanceof EntityCollection) {
                return $type;
            }

            return null;
        }

        throw new PropertyUndefinedException("Undefined property with name '" . $name . "'!", $this->reflection);
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

        if ($properties[$name]->isComputed()) {
            throw new PropertyException("Can not set computed property with name '" . $name . "'!", $this->reflection);
        }

        // @todo elaborate null values
        if ($value === null) {
            return;
        }

        // Validate value
        $properties[$name]->validateValue($value);

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

}