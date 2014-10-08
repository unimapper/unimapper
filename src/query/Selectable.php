<?php

namespace UniMapper\Query;

use UniMapper\Adapter,
    UniMapper\Exception,
    UniMapper\Association\ManyToOne,
    UniMapper\Association\ManyToMany,
    UniMapper\Association\OneToOne,
    UniMapper\Association\OneToMany;

abstract class Selectable extends Conditionable
{

    /** @var array */
    protected $associations = [
        "local" => [],
        "remote" => []
    ];

    public function associate($propertyName)
    {
        foreach (func_get_args() as $name) {

            if (!isset($this->entityReflection->getProperties()[$name])) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' not defined!"
                );
            }

            $property = $this->entityReflection->getProperties()[$name];
            if (!$property->isAssociation()) {
                throw new Exception\QueryException(
                    "Property '" . $name . "' is not defined as association!"
                );
            }

            $association = $property->getAssociation();
            if ($association->isRemote()) {
                $this->associations["remote"][$name] = $association;
            } else {
                $this->associations["local"][$name] = $association;
            }
        }

        return $this;
    }

    /**
     * Process ManyToOne association
     *
     * @param Adapter    $targetAdapter
     * @param ManyToMany $association
     * @param array      $primaryValues
     *
     * @return array
     */
    protected function manyToOne(
        Adapter $targetAdapter,
        ManyToOne $association,
        array $primaryValues
    ) {
        $query = $targetAdapter->createFind($association->getTargetResource());
        $query->setConditions(
            [
                [
                    $association->getTargetReflection()
                        ->getPrimaryProperty()
                        ->getMappedName(),
                    "IN",
                    $primaryValues,
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [
                $association->getTargetReflection()
                    ->getPrimaryProperty()
                    ->getMappedName()
            ]
        );
    }

    /**
     * Process ManyToMany association
     *
     * @param Adapter $currentAdapter
     * @param Adapter $targetAdapter
     * @param ManyToMany $association
     * @param array   $primaryValues
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo should be optimized with 1 query only on same adapters
     */
    protected function manyToMany(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        ManyToMany $association,
        array $primaryValues
    ) {
        if (!$association->isDominant()) {
            $currentAdapter = $targetAdapter;
        }

        $joinQuery = $currentAdapter->createFind(
            $association->getJoinResource(),
            [$association->getJoinKey(), $association->getReferenceKey()]
        );
        $joinQuery->setConditions(
            [[$association->getJoinKey(), "IN", $primaryValues, "AND"]]
        );

        $joinResult = $currentAdapter->execute($joinQuery);

        $this->adapterQueries[] = $joinQuery->getRaw();

        if (!$joinResult) {
            return [];
        }

        $joinResult = $this->groupResult(
            $joinResult,
            [
                $association->getReferenceKey(),
                $association->getJoinKey()
            ]
        );

        $targetQuery = $targetAdapter->createFind(
            $association->getTargetResource()
        );
        $targetQuery->setConditions(
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($joinResult),
                    "AND"
                ]
            ]
        );

        $targetResult = $targetAdapter->execute($targetQuery);

        $this->adapterQueries[] = $targetQuery->getRaw();

        if (!$targetResult) {
            return [];
        }

        $targetResult = $this->groupResult(
            $targetResult,
            [$association->getForeignKey()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new Exception\UnexpectedException(
                        "Can not merge associated result key '" . $targetKey
                        . "' not found in result from '"
                        . $association->getTargetResource()
                        . "'! Maybe wrong value in join table/resource."
                    );
                }
                $result[$originKey][] = $targetResult[$targetKey];
            }
        }

        return $result;
    }

    /**
     * Process OneToOne association
     *
     * @param Adapter  $targetAdapter
     * @param OneToOne $association
     * @param array    $primaryValues
     *
     * @return array
     */
    protected function oneToOne(
        Adapter $targetAdapter,
        OneToOne $association,
        array $primaryValues
    ) {
        $query = $targetAdapter->createFind($association->getTargetResource());
        $query->setConditions(
            [
                [
                    $association->getTargetReflection()
                        ->getPrimaryProperty()
                        ->getMappedName(),
                    "IN",
                    $primaryValues,
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [
                $association->getTargetReflection()
                    ->getPrimaryProperty()
                    ->getMappedName()
            ]
        );
    }

    protected function oneToMany(
        Adapter $targetAdapter,
        OneToMany $association,
        array $primaryValues
    ) {
        $query = $targetAdapter->createFind($association->getTargetResource());
        $query->setConditions(
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($primaryValues),
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        if (!$result) {
            return [];
        }

        return $result;
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