<?php

namespace UniMapper;

use UniMapper\Exceptions\InvalidArgumentException;

/**
 * Entity collection as ArrayList
 */
class EntityCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{

    /** @var string $entityClass Entity class */
    private $entityClass = null;

    /** @var array $data Data container */
    private $data = array();

    /**
     * Constructor
     *
     * @param string $entityClass Pass entity class and define collection type
     *
     * @return void
     */
    public function __construct($entityClass)
    {
        if (!is_subclass_of($entityClass, "UniMapper\Entity")) {
            throw new InvalidArgumentException("Class must be instance of entity!");
        }
        $this->entityClass = $entityClass;
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
     * Get entity class
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Returns an iterator over all items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Returns items count.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Replaces or appends a item.
     *
     * @param integer           $offset Index
     * @param \UniMapper\Entity $value  Value
     *
     * @return void
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof $this->entityClass) {
            throw new \Exception("Expected entity " . $this->entityClass . " but " . gettype($value) . " given!");
        }
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Returns an item.
     *
     * @param integer $key Key
     *
     * @return \UniMapper\Entity|null
     */
    public function offsetGet($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    /**
     * Determines whether a item exists.
     *
     * @param integer $key Key
     *
     * @return boolean
     */
    public function offsetExists($key)
    {
        return isset($this->data[$key]) || array_key_exists($key, $this->data);
    }

    /**
     * Removes the element at the specified position in this data.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Convert collection to array
     *
     * @return array
     */
    public function toArray()
    {
        $output = array();
        foreach ($this->data as $index => $entity) {
            $output[$index] = $entity->toArray(true);
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
        return $this->toArray();
    }

    /**
     * Get entity by primary value
     *
     * @param mixed $value
     *
     * @return \UniMapper\Entity|false
     */
    public function getByPrimary($value)
    {
        foreach ($this->data as $entity) {

            $primaryPropertyName = $entity->getReflection()->getPrimaryProperty()->getName();
            $primaryValue = $entity->{$primaryPropertyName};
            if ($primaryValue === $value && $primaryValue !== null) {
                return $entity;
            }
        }
        return false;
    }

}