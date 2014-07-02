<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\NamingConvention as NC;

abstract class Cache implements Cache\ICache
{

    protected $entityKeyPrefix = "EntityReflection-";

    /**
     * Load entity reflection from cache
     *
     * @param string $class Entity class
     *
     * @return \UniMapper\Reflection\Entity
     */
    final public function loadEntityReflection($class)
    {
        $key = $this->entityKeyPrefix . NC::classToName($class, NC::$entityMask);

        $reflection = $this->load($key);
        if (!$reflection) {
            $reflection = new Reflection\Entity($class);
            $this->save($key, $reflection, $reflection->getFileName());
        }
        return $reflection;
    }

}