<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Entity;
use UniMapper\Exception;
use UniMapper\Reflection;

class ManyToOne extends Single
{

    public function __construct(
        $propertyName,
        Reflection\Entity $sourceReflection,
        Reflection\Entity $targetReflection,
        array $mapBy
    ) {
        parent::__construct(
            $propertyName,
            $sourceReflection,
            $targetReflection,
            $mapBy
        );

        if (!$targetReflection->hasPrimary()) {
            throw new Exception\AssociationException(
                "Target entity must have defined primary when N:1 relation used!"
            );
        }

        if (!isset($mapBy[0])) {
            throw new Exception\AssociationException(
                "You must define a reference key!"
            );
        }
    }

    public function getKey()
    {
        return $this->getReferencingKey();
    }

    public function getReferencingKey()
    {
        return $this->mapBy[0];
    }

    public function getTargetPrimaryKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource());

        // Set target conditions
        $conditions = $this->conditions;
        $conditions[] = [
            $this->getTargetPrimaryKey(),
            "IN",
            $primaryValues,
            "AND"
        ];
        $query->setConditions($conditions);

        $result = $targetAdapter->execute($query);

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [$this->getTargetPrimaryKey()]
        );
    }

    public function saveChanges($primaryValue, Connection $connection, Entity $entity)
    {
        if (!$entity->getReflection()->hasPrimary()) {
            throw new Exception\InvalidArgumentException(
                "Only entity with primary can save changes!"
            );
        }

        $sourceAdapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $primaryName = $entity->getReflection()->getPrimaryProperty()->getName();

        switch ($entity->getChangeType()) {
        case Entity::CHANGE_ATTACH:

            $adapterQuery = $sourceAdapter->createUpdateOne(
                $this->getSourceResource(),
                $this->getPrimaryKey(),
                $primaryValue,
                [$this->getReferencingKey() => $entity->{$primaryName}]
            );
            $sourceAdapter->execute($adapterQuery);
            break;
        default:
            break;
        }
    }

}