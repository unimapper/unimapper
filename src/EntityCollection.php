<?php

namespace UniMapper;

/**
 * Entity collection as ArrayList
 */
class EntityCollection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{

    /** @var string $entityClass Entity class */
    private $entityClass = null;

    /** @var array $container Array container */
    private $container = array();

    /**
     * Constructor
     *
     * @param string $entity Pass entity class and define collection type
     *
     * @return void
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
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
        return new \ArrayIterator($this->container);
    }

    /**
     * Returns items count.
     *
     * @return integer
     */
    public function count()
    {
        return count($this->container);
    }

    /**
     * Replaces or appends a item.
     *
     * @param integer         $offset Index
     * @param \UniMapper\Entity $value  Value
     *
     * @return void
     *
     * @throws \Exception
     */
    public function offsetSet($offset, $value)
    {
        if (!$value instanceof $this->entityClass) {
            throw new \Exception(
                "Expected entity " . $this->entityClass . " but instance of "
                . get_class($value) . " given!"
            );
        }
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
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
        if (isset($this->container[$key])) {
            return $this->container[$key];
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
        return isset($this->container[$key]) || array_key_exists($key, $this->container);
    }

    /**
     * Removes the element at the specified position in this container.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->container;
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
     * Merge collection
     *
     * @param \UniMapper\EntityCollection $collection
     *
     * @return \UniMapper\EntityCollection
     */
    public function merge(\UniMapper\EntityCollection $collection)
    {
        foreach ($collection as $primary => $entity) {
            if (isset($this->container[$primary])
                && isset($collection[$primary])
            ) {
                $this->container[$primary]->merge($collection[$primary]);
            } else {
                unset($this->container[$primary]);
            }
        }
        return $this;
    }

    public function getKeys()
    {
        return array_keys($this->container);
    }

}