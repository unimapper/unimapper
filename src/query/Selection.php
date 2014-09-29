<?php

namespace UniMapper\Query;

use UniMapper\Adapter,
    UniMapper\Exception,
    UniMapper\Reflection\Entity\Property\Association\ManyToOne,
    UniMapper\Reflection\Entity\Property\Association\ManyToMany,
    UniMapper\Reflection\Entity\Property\Association\OneToOne,
    UniMapper\Reflection\Entity\Property\Association\OneToMany;

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
        $mapping = $targetAdapter->getMapping();

        $result = $targetAdapter->find(
            $association->getTargetResource(),
            $mapping->unmapSelection([]),
            $mapping->unmapConditions(
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
            )
        );

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

        $joinResult = $currentAdapter->find(
            $association->getJoinResource(),
            $currentAdapter->getMapping()->unmapSelection(
                [$association->getJoinKey(), $association->getReferenceKey()]
            ),
            $currentAdapter->getMapping()->unmapConditions(
                [[$association->getJoinKey(), "IN", $primaryValues, "AND"]]
            )
        );

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

        $targetResult = $targetAdapter->find(
            $association->getTargetResource(),
            $targetAdapter->getMapping()->unmapSelection([]),
            $targetAdapter->getMapping()->unmapConditions(
                [
                    [
                        $association->getForeignKey(),
                        "IN",
                        array_keys($joinResult),
                        "AND"
                    ]
                ]
            )
        );
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
        $mapping = $targetAdapter->getMapping();

        $result = $targetAdapter->find(
            $association->getTargetResource(),
            $mapping->unmapSelection([]),
            $mapping->unmapConditions(
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
            )
        );

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
        $mapping = $targetAdapter->getMapping();

        $result = $targetAdapter->find(
            $association->getTargetResource(),
            $mapping->unmapSelection([]),
            $mapping->unmapConditions(
                [
                    [
                        $association->getForeignKey(),
                        "IN",
                        array_keys($primaryValues),
                        "AND"
                    ]
                ]
            )
        );

        if (!$result) {
            return [];
        }

        return $result;
    }

}