<?php

namespace UniMapper\Reflection;

use UniMapper\Cache;
use UniMapper\Exception;
use UniMapper\NamingConvention as UNC;

class Loader
{

    /** @var Cache\ICache $cache */
    private static $cache;

    /** @var array */
    private static $reflections = [];

    public static function setCache(Cache\ICache $cache)
    {
        self::$cache = $cache;
    }

    public static function getCache()
    {
        return self::$cache;
    }

    public static function register(Entity $reflection)
    {
        if (isset(self::$reflections[$reflection->getClassName()])) {
            throw new Exception\InvalidArgumentException(
                "Reflection of " . $reflection->getClassName() . " already registered!"
            );
        }
        self::$reflections[$reflection->getClassName()] = $reflection;
    }

    public static function get($class)
    {
        if (isset(self::$reflections[$class])) {
            return self::$reflections[$class];
        }
        return false;
    }

    /**
     * Load entity reflection
     *
     * @param mixed $entity Entity object, class or name
     */
    public static function load($entity)
    {
        if (is_object($entity)) {
            $class = get_class($entity);
        } else {
            $class = (string) $entity;
        }

        if (!is_subclass_of($class, "UniMapper\Entity")) {
            $class = UNC::nameToClass($entity, UNC::$entityMask);
        }

        if (!class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                "Entity class " . $class . " not found!"
            );
        }

        if (isset(self::$reflections[$class])) {
            return self::$reflections[$class];
        }

        if (self::$cache) {

            $reflection = self::$cache->load($class);
            if (!$reflection) {

                $reflection = new Entity($class);
                self::$cache->save(
                    $class,
                    $reflection,
                    [
                        Cache\ICache::FILES => self::getRelatedFiles($name),
                        Cache\ICache::TAGS => [Cache\ICache::TAG_REFLECTION]
                    ]
                );
            }
            return $reflection;
        } else {
            $reflection = new Entity($class);
        }

        return $reflection;
    }

    /**
     * Get entity's related files
     *
     * @param string $name Entity name
     * @param array  $files
     *
     * @return array
     */
    public static function getRelatedFiles($name, array $files = [])
    {
        $reflection = self::load($name);
        if (in_array($reflection->getFileName(), $files)) {
            return $files;
        }

        $files[] = $reflection->getFileName();

        foreach ($reflection->getProperties() as $property) {
            if (in_array($property->getType(), [Property::TYPE_COLLECTION, Property::TYPE_ENTITY])) {
                $files += self::getRelatedFiles($property->getTypeOption(), $files);
            }
        }
        return $files;
    }

}