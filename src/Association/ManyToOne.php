<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Entity;
use UniMapper\Exception;

class ManyToOne extends Single
{

    public function __construct(
        $propertyName,
        Entity\Reflection $sourceReflection,
        Entity\Reflection $targetReflection,
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
        // Remove empty primary values
        $primaryValues = array_filter(array_unique($primaryValues));
        if (empty($primaryValues)) {
            return [];
        }

        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource(), $this->getTargetSelection());

        // Set target conditions
        $filter = $this->filter;
        $filter[$this->getTargetPrimaryKey()][Entity\Filter::EQUAL] = $primaryValues;
        if ($this->getTargetFilter()) {
            $filter = array_merge($connection->getMapper()->unmapFilter($this->getTargetReflection(), $this->getTargetFilter()), $filter);
        }
        $query->setFilter($filter);

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
        $reflection = Entity\Reflection::load($entity);

        if (!$reflection->hasPrimary()) {
            throw new Exception\InvalidArgumentException(
                "Only entity with primary can save changes!"
            );
        }

        $sourceAdapter = $connection->getAdapter($this->sourceReflection->getAdapterName());

        $primaryName = $reflection->getPrimaryProperty()->getName();

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