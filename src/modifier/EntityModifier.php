<?php

namespace UniMapper\Modifier;

use UniMapper\Entity,
    UniMapper\Adapter;

class EntityModifier extends \UniMapper\Modifier
{

    private $attached;

    public function attach(Entity $entity)
    {
        $this->validateEntity($entity, true);
        $this->attached = $entity->{$entity->getReflection()->getPrimaryProperty()->getName()};
    }

    public function getAttached()
    {
        return $this->attached;
    }

    protected function saveManyToOne(
        $primaryValue,
        Adapter $sourceAdapter,
        Adapter $targetAdapter
    ) {
        if ($this->getAttached()) {

            $adapterQuery = $sourceAdapter->createUpdateOne(
                $this->associationReflection->getSourceResource(),
                $this->associationReflection->getPrimaryKey(),
                $primaryValue,
                [$this->associationReflection->getReferenceKey() => $this->getAttached()]
            );
            $sourceAdapter->execute($adapterQuery);
        }
    }

    protected function findManyToOne(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createSelect($this->associationReflection->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->associationReflection->getTargetPrimaryKey(),
                    "IN",
                    $primaryValues,
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [$this->associationReflection->getTargetPrimaryKey()]
        );
    }

    protected function findOneToOne(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createSelect($this->associationReflection->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->associationReflection->getTargetPrimaryKey(),
                    "IN",
                    $primaryValues,
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [$this->associationReflection->getTargetPrimaryKey()]
        );
    }

}