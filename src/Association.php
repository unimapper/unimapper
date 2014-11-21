<?php

namespace UniMapper;

abstract class Association
{

    /** @var string $expression Regular expression for definition */
    protected $expression;

    /** @var array $matches Matched items by regular definition */
    protected $matches;

    /** @var \UniMapper\Reflection\Entity\Property */
    protected $propertyReflection;

    /** @var \UniMapper\Reflection\Entity */
    protected $targetReflection;

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        if (!preg_match("/" . $this->expression . "/", $definition, $matches)) {
            throw new Exception\DefinitionException(
                "Invalid association type definition '". $definition . "'!",
                Exception\DefinitionException::DO_NOT_FAIL
            );
        }
        $this->matches = $matches;

        $this->propertyReflection = $propertyReflection;
        $this->targetReflection = $targetReflection;
    }

    public function getPrimaryKey()
    {
        return $this->propertyReflection->getEntityReflection()
            ->getPrimaryProperty()
            ->getName(true);
    }

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
        return $this->targetReflection->getAdapterReflection()->getResource();
    }

    public function getSourceResource()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterReflection()->getResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterReflection()->getName();
    }

    public function isRemote()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterReflection()->getName()
            !== $this->targetReflection->getAdapterReflection()->getName();
    }

    public function getPropertyName()
    {
        return $this->propertyReflection->getName();
    }

    protected function validateEntity(Entity $entity, $requirePrimary = false)
    {
        $class = $this->getTargetReflection()->getClassName();

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