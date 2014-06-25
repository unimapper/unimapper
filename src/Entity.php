<?php

namespace UniMapper;

use UniMapper\Validator,
    UniMapper\EntityCollection,
    UniMapper\Query,
    UniMapper\Reflection,
    UniMapper\Cache\ICache,
    UniMapper\Exceptions\PropertyException,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\PropertyReadonlyException,
    UniMapper\Exceptions\PropertyUndefinedException;

/**
 * Entity is ancestor for all entities and provides global methods, which
 * can be used in every new entity object.
 */
abstract class Entity implements \JsonSerializable, \Serializable, \Iterator
{

    /** Validator trait */
    use Validator;

    /** @var \UniMapper\Reflection\Entity $reflection */
    private $reflection;

    /** @var array $data Stored variables */
    private $data = [];

    /** @var array $mappers */
    private $mappers;

    /** @var \UniMapper\Cache\ICache $cache */
    private $cache;

    /** @var string $iteration List of property names */
    private $iteration;

    public function __construct(ICache $cache = null, Reflection\Entity $reflection = null)
    {
        $this->cache = $cache;
        if ($reflection) {
            if ($reflection->getClassName() !== get_called_class()) {
                throw new \Exception("Expected reflection of class '" . get_called_class() . "' but reflection of '" . $reflection->getClassName() . "' given!");
            }
            $this->reflection = $reflection;
        }
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

        $this->iteration = array_merge(
            array_keys($this->reflection->getProperties()),
            $this->reflection->getPublicProperties()
        );
        $this->rewind();
    }

    /**
     * Serialize entity data and public properties
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array_merge($this->data, $this->getPublicPropertyValues()));
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
        return count($this->mappers) > 0;
    }

    /**
     * Set entity as active, so you can save and delete entity after that.
     *
     * @param array $mappers
     *
     * @throws \Exception
     */
    public function setActive(array $mappers)
    {
        if ($this->isActive()) {
            throw new \Exception("Entity is already active!");
        }
        $this->mappers = $mappers;
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

        if (!$this->reflection->hasPrimaryProperty()) {
            throw new \Exception("Can not save entity without primary property!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = null;
        if (isset($this->data[$primaryName])) {
            $primaryValue = $this->data[$primaryName];
        }

        if ($primaryValue === null) {
            // Insert

            $query = new Query\Insert($this->reflection, $this->mappers, $this->data);
            $primaryValue = $query->execute();
        } else {
            // Update

            $query = new Query\UpdateOne($this->reflection, $this->mappers, $primaryValue, $this->data);
            $query->execute();
        }

        // Set primary value
        $this->data[$primaryName] = $primaryValue;
    }

    public function delete()
    {
        if (!$this->isActive()) {
            throw new \Exception("Entity is not active!");
        }

        if (!$this->reflection->hasPrimaryProperty()) {
            throw new \Exception("Can not delete entity without primary property!");
        }

        $primaryName = $this->reflection->getPrimaryProperty()->getName();
        $primaryValue = $this->{$primaryName};
        if ($primaryValue === null) {
            throw new \Exception("Primary value must be set!");
        }

        $query = new Query\Delete($this->reflection, $this->mappers);
        $query->where($primaryName, "=", $primaryValue)->execute();
    }

    /**
     * Import and try to convert values automatically if possible, skip readonly
     *
     * @param mixed $values Traversable structure (array/object)
     */
    public function import($values)
    {
        if (!Validator::validateTraversable($values)) {
            throw new \Exception("Values must be traversable data!");
        }

        foreach ($values as $name => $value) {

            try {
                $this->{$name} = $value;
            } catch (\Exception $e) {

                if ($e instanceof PropertyTypeException) {
                    $this->data[$name] = $this->reflection->getProperties()[$name]->convertValue($value);
                } elseif ($e instanceof PropertyReadonlyException) {
                    continue;
                } else {
                    throw new \Exception($e->getMessage());
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
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $properties = $this->reflection->getProperties();
        if (!isset($properties[$name])) {
            throw new PropertyUndefinedException("Undefined property with name '" . $name . "'!", $this->reflection);
        }

        if (!$properties[$name]->isWritable()) {
            throw new PropertyReadonlyException("Property '" . $name . "' is not writable!");
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

            $value = $this->{$propertyName};
            if (($value instanceof EntityCollection || $value instanceof Entity) && $nesting) {
                $output[$propertyName] = $value->toArray($nesting);
            } else {
                $output[$propertyName] = $value;
            }
        }

        return array_merge($output, $this->getPublicPropertyValues());
    }

    private function getPublicPropertyValues()
    {
        $result = [];
        foreach ($this->reflection->getPublicProperties() as $name) {
            $result[$name] = $this->{$name};
        }
        return $result;
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

    public function rewind()
    {
        reset($this->iteration);
    }

    public function current()
    {
        return $this->{$this->key()};
    }

    public function key()
    {
        return current($this->iteration);
    }

    public function next()
    {
        next($this->iteration);
    }

    public function valid()
    {
        return key($this->iteration) !== null;
    }

}