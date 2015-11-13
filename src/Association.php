<?php

namespace UniMapper;

abstract class Association
{

    /**
     * @var Entity\Reflection
     *
     * @todo quick fix for traits
     */
    protected $entityReflection;

    /** @var Entity\Reflection */
    protected $sourceReflection;

    /** @var Entity\Reflection */
    protected $targetReflection;

    /** @var bool */
    protected $dominant = true;

    /** @var array */
    protected $mapBy = [];

    /** @var string */
    protected $propertyName;

    public function __construct(
        $propertyName,
        Entity\Reflection $sourceReflection,
        Entity\Reflection $targetReflection,
        array $mapBy,
        $dominant = true
    ) {
        $this->propertyName = $propertyName;
        $this->sourceReflection = $this->entityReflection = $sourceReflection; // @todo quick fix for traits
        $this->targetReflection = $targetReflection;
        $this->dominant = (bool) $dominant;
        $this->mapBy = $mapBy;

        if (!$this->sourceReflection->hasAdapter()) {
            throw new Exception\AssociationException(
                "Can not use associations while source entity "
                . $sourceReflection->getName()
                . " has no adapter defined!"
            );
        }

        if (!$this->targetReflection->hasAdapter()) {
            throw new Exception\AssociationException(
                "Can not use associations while target entity "
                . $targetReflection->getName() . " has no adapter defined!"
            );
        }
    }

    public function getPrimaryKey()
    {
        return $this->sourceReflection->getPrimaryProperty()->getUnmapped();
    }

    /**
     * Key name that refers target results to source entity
     *
     * @return type
     */
    public function getKey()
    {
        return $this->getPrimaryKey();
    }

    public function getTargetReflection()
    {
        return $this->targetReflection;
    }

    public function getTargetResource()
    {
        return $this->targetReflection->getAdapterResource();
    }

    public function getSourceResource()
    {
        return $this->sourceReflection->getAdapterResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterName();
    }

    public function isRemote()
    {
        return $this->sourceReflection->getAdapterName()
            !== $this->targetReflection->getAdapterName();
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Group associative array
     *
     * @param array $original
     * @param array $keys
     * @param int   $level
     *
     * @return array
     *
     * @link http://tigrou.nl/2012/11/26/group-a-php-array-to-a-tree-structure/
     *
     * @throws \Exception
     */
    protected function groupResult(array $original, array $keys, $level = 0)
    {
        $converted = [];
        $key = $keys[$level];
        $isDeepest = sizeof($keys) - 1 == $level;

        $level++;

        $filtered = [];
        foreach ($original as $k => $subArray) {

            $subArray = (array) $subArray;
            if (!isset($subArray[$key])) {
                throw new \Exception(
                    "Index '" . $key . "' not found on level '" . $level . "'!"
                );
            }

            $thisLevel = $subArray[$key];

            if (is_object($thisLevel)) {
                $thisLevel = (string) $thisLevel;
            }

            if ($isDeepest) {
                $converted[$thisLevel] = $subArray;
            } else {
                $converted[$thisLevel] = [];
            }
            $filtered[$thisLevel][] = $subArray;
        }

        if (!$isDeepest) {
            foreach (array_keys($converted) as $value) {
                $converted[$value] = $this->groupResult(
                    $filtered[$value],
                    $keys,
                    $level
                );
            }
        }

        return $converted;
    }

}