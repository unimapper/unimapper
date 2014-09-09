<?php

namespace UniMapper\Query;

use UniMapper\Adapter,
    UniMapper\Exception,
    UniMapper\Reflection\Entity\Property\Association\HasMany,
    UniMapper\Reflection\Entity\Property\Association\BelongsToMany;

abstract class Selection extends \UniMapper\Query
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
                throw new Exception\QueryException("Property '" . $name . "' not defined!");
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
     * Process HasMany association
     *
     * @param Adapter $currentAdapter
     * @param Adapter $targetAdapter
     * @param HasMany $association
     * @param array   $primaryValues
     *
     * @return array
     *
     * @throws \Exception
     *
     * @todo should be optimized with 1 query only on same adapters
     */
    protected function hasMany(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        HasMany $association,
        array $primaryValues
    ) {
        if (!$association->isDominant()) {
            $currentAdapter = $targetAdapter;
        }

        $joinResult = $currentAdapter->find(
            $association->getJoinResource(),
            [$association->getJoinKey(), $association->getReferenceKey()],
            [[$association->getJoinKey(), "IN", $primaryValues, "AND"]]
        );
        if (!$joinResult) {
            return [];
        }

        $joinResult = $this->groupArray(
            $joinResult,
            [
                $association->getReferenceKey(),
                $association->getJoinKey()
            ]
        );

        $targetResult = $targetAdapter->find(
            $association->getTargetResource(),
            [],
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($joinResult),
                    "AND"
                ]
            ]
        );
        if (!$targetResult) {
            return [];
        }

        $targetResult = $this->groupArray(
            $targetResult,
            [$association->getForeignKey()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new \Exception(
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

    protected function belongsToMany(
        Adapter $targetAdapter,
        BelongsToMany $association,
        array $primaryValues
    ) {
        $result = $targetAdapter->find(
            $association->getTargetResource(),
            [],
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($primaryValues), "AND"
                ]
            ]
        );

        if (!$result) {
            return [];
        }

        return $result;
    }

}