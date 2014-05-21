<?php

namespace UniMapper;

use UniMapper\Validator,
    UniMapper\EntityCollection,
    UniMapper\Query,
    UniMapper\Mapper,
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

    /** Validator trait */
    use Validator;

    /** @var \UniMapper\Reflection\Entity $reflection */
    private $reflection;

    /** @var array $data Stored variables */
    private $data = [];

    /** @var \UniMapper\Mapper $mapper */
    private $mapper;

    /** @var \UniMapper\Cache\ICache $cache */
    private $cache;

    public function __construct(ICache $cache = null)
    {
        $this->cache = $cache;
        $this->initialize();
    }

    /**
     * Initialize entity with reflection
     */
    private function initialize()
    {
        if (!$this->reflection) {

            $className = get_called_class();
            if ($this->cache) {

                $key = "entity-" . $className;
                $this->reflection = $this->cache->load($key);
                if (!$this->reflection) {
                    $this->reflection = new Reflection\Entity($className);
                    $this->cache->save($key, $this->reflection, $this->reflection->getFileName());
                }
            } else {
                $this->reflection = new Reflection\Entity($className);
            }
        }
    }

    /**
     * Serialize entity data and public properties
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array_merge($this->data, $this->getPublicVars()));
    }

    public function unserialize($data)
    {
        $this->initialize();
        foreach (unserialize($data) as $name => $value) {
            $this->{$name} = $value;
        }
    }

    public function isActive()
    {
        return $this->mapper instanceof Mapper;
    }

    /**
     * Set entity as active, so you can save and delete entity after that.
     *
     * @param \UniMapper\Mapper $mapper
     *
     * @throws \Exception
     */
    public function setActive(Mapper $mapper)
    {
        if ($this->isActive()) {
            throw new \Exception("Entity is already active!");
        }
        $this->mapper = $mapper;
    }

    public function getMapper()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }
        return $this->mapper;
    }

    /**
     * Save entity, update if primary value set and insert if not
     *
     * @throws \Exception
     */
    public function save()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = null;
        if (isset($this->data[$primaryName])) {
            $primaryValue = $this->data[$primaryName];
        }

        if ($primaryValue === null) {
            // Insert

            $query = new Query\Insert($this->reflection, $this->mapper, $this);
            $this->data[$primaryName] = $query->execute();
        } else {
            // Update

            $query = new Query\Update($this->reflection, $this->mapper, $this);
            $query->where($primaryName, "=", $primaryValue);
            $query->execute();
        }
    }

    public function delete()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = $this->{$primaryName};
        if ($primaryValue === null) {
            throw new \Exception("Primary value must be set!");
        }

        $query = new Query\Delete($this->reflection, $this->mapper);
        $query->where($primaryName, "=", $primaryValue)->execute();
    }

    /**
     * Import and try to convert values automatically if possible
     *
     * @param mixed $values Traversable structure (array/object)
     */
    public function import($values)
    {
        if (!Validator::validateTraversable($values)) {
            throw new \Exception("Values must be traversable data!");
        }

        $properties = $this->reflection->getProperties();
        foreach ($values as $propertyName => $value) {

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
                    if (Validator::validateTraversable($value)) {
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
                } elseif ($propertyType instanceof EntityCollection && Validator::validateTraversable($value)) {
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

        if ($value !== null) {
            $properties[$name]->validateValue($value);
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

        return array_merge($output, $this->getPublicVars());
    }

    private function getPublicVars()
    {
        $vars = [];
        $reflection = (new \ReflectionObject($this));
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $vars[$property->getName()] = $property->getValue($this);
        }
        return $vars;
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