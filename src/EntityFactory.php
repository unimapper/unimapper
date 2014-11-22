<?php

namespace UniMapper;

use UniMapper\NamingConvention as UNC;

class EntityFactory
{

    /** @var Cache\ICache $cache */
    private $cache;

    public function __construct(Cache\ICache $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * Create entity
     *
     * @param string $name
     * @param mixed  $values Iterable value like array or stdClass object
     *
     * @return Entity
     */
    public function createEntity($name, $values = null)
    {
        return $this->getEntityReflection($name)->createEntity($values);
    }

    /**
     * Create entity collection
     *
     * @param string $name
     * @param mixed  $values Iterable value like array or stdClass object
     *
     * @return EntityCollection
     */
    public function createCollection($name, $values = null)
    {
        // Create empty collection
        $collection = new EntityCollection($this->getEntityReflection($name));

        // Add values
        if ($values) {

            $class = $collection->getEntityReflection()->getClassName();
            foreach ($values as $item) {

                if (!$item instanceof $class) {
                    $item = $this->createEntity($name, $item);
                }
                $collection[] = $item;
            }
        }

        return $collection;
    }

    /**
     * Get entity reflection
     *
     * @param string $name Entity name
     */
    public function getEntityReflection($name)
    {
        $class = UNC::nameToClass($name, UNC::$entityMask);

        if ($this->cache) {

            $reflection = $this->cache->load($class);
            if (!$reflection) {

                $reflection = new Reflection\Entity($class);

                $this->cache->save(
                    $class,
                    $reflection,
                    [
                        Cache\ICache::FILES => $reflection->getRelatedFiles(
                            [$reflection->getFileName()]
                        ),
                        Cache\ICache::TAGS => [Cache\ICache::TAG_REFLECTION]
                    ]
                );
            }
            return $reflection;
        }

        return $reflection = new Reflection\Entity($class);
    }

    public function getCache()
    {
        return $this->cache;
    }

}