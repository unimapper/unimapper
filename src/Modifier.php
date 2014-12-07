<?php

namespace UniMapper;

abstract class Modifier
{

    protected $associationReflection;

    public function __construct(Reflection\Association $associationReflection)
    {
        $this->associationReflection = $associationReflection;
    }

    public function getAssociation()
    {
        return $this->associationReflection;
    }

    public function load(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        if ($this->associationReflection instanceof Reflection\Association\ManyToMany) {
            return $this->findManyToMany($currentAdapter, $targetAdapter, $primaryValues);
        } elseif ($this->associationReflection instanceof Reflection\Association\ManyToOne) {
            return $this->findManyToOne($currentAdapter, $targetAdapter, $primaryValues);
        } elseif ($this->associationReflection instanceof Reflection\Association\OneToMany) {
            return $this->findOneToMany($currentAdapter, $targetAdapter, $primaryValues);
        } elseif ($this->associationReflection instanceof Reflection\Association\OneToOne) {
            return $this->findOneToOne($currentAdapter, $targetAdapter, $primaryValues);
        } else {
            throw new Exception\UnexpectedException(
                "Unsupported association " . get_class($this->associationReflection) . "!"
            );
        }
    }

    public function save(
        Adapter $sourceAdapter,
        Adapter $targetAdapter,
        $primaryValue
    ) {
        if ($this->associationReflection instanceof Reflection\Association\ManyToMany) {

            if ($this->associationReflection->isRemote() && !$this->associationReflection->isDominant()) {
                $sourceAdapter = $targetAdapter;
            }

            $this->saveManyToMany($primaryValue, $sourceAdapter, $targetAdapter);
            $this->saveManyToMany(
                $primaryValue,
                $sourceAdapter,
                $targetAdapter,
                Adapter\IAdapter::ASSOC_REMOVE
            );
        } elseif ($this->associationReflection instanceof Reflection\Association\ManyToOne) {
            $this->saveManyToOne($primaryValue, $sourceAdapter, $targetAdapter);
        } else {
            throw new Exception\UnexpectedException(
                "Unsupported association " . get_class($this->associationReflection) . "!"
            );
        }
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
     * @throws Exception\UnexpectedException
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
                throw new Exception\UnexpectedException(
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

    protected function validateEntity(Entity $entity, $requirePrimary = false)
    {
        $class = $this->associationReflection->getTargetReflection()->getClassName();

        if (!$entity instanceof $class) {
            throw new Exception\AssociationException(
                "You can associate only " . $class . " entity!"
            );
        }

        if ($requirePrimary) {

            $primaryName = $entity->getReflection()->getPrimaryProperty()->getName();
            if (empty($entity->{$primaryName})) {
                throw new Exception\AssociationException(
                    "Primary value is required in association modifications!"
                );
            }
        }
    }

}