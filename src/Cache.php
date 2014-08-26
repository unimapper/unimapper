<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\NamingConvention as NC;

abstract class Cache implements Cache\ICache
{

    /** @var string $entityKeyPrefix */
    protected $entityKeyPrefix = "UniMapper-Reflection-Entity-";

    /**
     * Load entity reflection from cache
     *
     * @param string $class Entity class
     *
     * @return \UniMapper\Reflection\Entity
     */
    public function loadEntityReflection($class)
    {
        $key = $this->entityKeyPrefix . NC::classToName($class, NC::$entityMask);

        $reflection = $this->load($key);
        if (!$reflection) {

            $reflection = new Reflection\Entity($class);
            $this->save(
                $key,
                $reflection,
                $this->_getRelatedFiles($reflection)
            );
        }
        return $reflection;
    }

    /**
     * Get related entity class files
     *
     * @param \UniMapper\Reflection\Entity $reflection
     * @param array                        $files
     *
     * @return array
     */
    private function _getRelatedFiles(Reflection\Entity $reflection, array $files = [])
    {
        foreach ($reflection->getRelated() as $childReflection) {

            $fileName = $childReflection->getFileName();
            if (!array_search($fileName, $files, true)) {

                $files[] = $fileName;
                if ($childReflection->getRelated()) {
                    $files = array_merge($files, $this->_getRelatedFiles($childReflection, $files));
                }
            }
        }

        return array_values(array_unique($files));
    }

}