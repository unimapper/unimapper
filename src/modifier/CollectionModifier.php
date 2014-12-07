<?php

namespace UniMapper\Modifier;

use UniMapper\Entity,
    UniMapper\Adapter;

class CollectionModifier extends \UniMapper\Modifier
{

    /** @var array */
    private $attached = [];

    /** @var array */
    private $detached = [];

    /** @var array */
    private $added = [];

    /** @var array */
    private $removed = [];

    public function attach(Entity $entity)
    {
        $this->_manipulate($entity, "attached");
    }

    public function detach(Entity $entity)
    {
        $this->_manipulate($entity, "detached");
    }

    public function add(Entity $entity)
    {
        $this->validateEntity($entity);
        $this->added[] = $entity;
    }

    public function remove(Entity $entity)
    {
        $this->validateEntity($entity, true);
        $this->removed[] = $entity;
    }

    public function getAttached()
    {
        return $this->attached;
    }

    public function getDetached()
    {
        return $this->detached;
    }

    public function getAdded()
    {
        return $this->added;
    }

    public function getRemoved()
    {
        return $this->removed;
    }

    private function _manipulate($entity, $action)
    {
        $this->validateEntity($entity, true);

        $primary = $entity->{$entity->getReflection()->getPrimaryProperty()->getName()};

        if (!in_array($primary, $this->{$action}, true)) {
            array_push($this->{$action}, $primary);
        }
    }

    /**
     * @todo should be optimized with 1 query only on same adapters
     */
    protected function findManyToMany(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        if (!$this->associationReflection->isDominant()) {
            $currentAdapter = $targetAdapter;
        }

        $joinQuery = $currentAdapter->createSelect(
            $this->associationReflection->getJoinResource(),
            [$this->associationReflection->getJoinKey(), $this->associationReflection->getReferenceKey()]
        );
        $joinQuery->setConditions(
            [[$this->associationReflection->getJoinKey(), "IN", $primaryValues, "AND"]]
        );

        $joinResult = $currentAdapter->execute($joinQuery);

        if (!$joinResult) {
            return [];
        }

        $joinResult = $this->groupResult(
            $joinResult,
            [
                $this->associationReflection->getReferenceKey(),
                $this->associationReflection->getJoinKey()
            ]
        );

        $targetQuery = $targetAdapter->createSelect(
            $this->associationReflection->getTargetResource()
        );
        $targetQuery->setConditions(
            [
                [
                    $this->associationReflection->getForeignKey(),
                    "IN",
                    array_keys($joinResult),
                    "AND"
                ]
            ]
        );

        $targetResult = $targetAdapter->execute($targetQuery);
        if (!$targetResult) {
            return [];
        }

        $targetResult = $this->groupResult(
            $targetResult,
            [$this->associationReflection->getForeignKey()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new Exception\UnexpectedException(
                        "Can not merge associated result key '" . $targetKey
                        . "' not found in result from '"
                        . $this->associationReflection->getTargetResource()
                        . "'! Maybe wrong value in join table/resource."
                    );
                }
                $result[$originKey][] = $targetResult[$targetKey];
            }
        }

        return $result;
    }

    protected function findOneToMany(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createSelect($this->associationReflection->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->associationReflection->getForeignKey(),
                    "IN",
                    array_keys($primaryValues),
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (!$result) {
            return [];
        }

        return $result;
    }

    protected function saveManyToMany(
        $primaryValue,
        Adapter $joinAdapter,
        Adapter $targetAdapter,
        $action = Adapter\IAdapter::ASSOC_ADD
    ) {
        if ($action === Adapter\IAdapter::ASSOC_REMOVE) {
            $assocKeys = $this->getDetached();
            $entities = $this->getRemoved();
        } else {
            $assocKeys = $this->getAttached();
            $entities = $this->getAdded();
        }

        foreach ($entities as $entity) {

            if ($action === Adapter\IAdapter::ASSOC_REMOVE) {

                $targetPrimaryName = $this->associationReflection->getTargetReflection()
                    ->getPrimaryProperty()
                    ->getName(true);
                $targetAdapter->execute(
                    $targetAdapter->createDeleteOne(
                        $this->associationReflection->getTargetResource(),
                        $targetPrimaryName,
                        $entity->{$targetPrimaryName}
                    )
                );
                $assocKeys[] = $entity->{$targetPrimaryName};
            } else {
                $assocKeys[] = $targetAdapter->execute(
                    $targetAdapter->createInsert($this->associationReflection->getTargetResource(), $entity->getData())
                );
            }
        }

        if ($assocKeys) {

            $adapterQuery = $joinAdapter->createModifyManyToMany(
                $this,
                $primaryValue,
                array_unique($assocKeys),
                $action
            );
            $joinAdapter->execute($adapterQuery);
        }
    }

}