<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Entity\Filter;
use UniMapper\Exception\InvalidArgumentException;
use UniMapper\Exception\AssociationException;
use UniMapper\Query;
use UniMapper\Entity;

abstract class Single extends \UniMapper\Association
{

    use Query\Filterable;

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
            throw new AssociationException(
                "Target entity must have defined primary when N:1 relation used!"
            );
        }

        if (!isset($mapBy[0])) {
            throw new AssociationException("You must define a reference key!");
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
        return $this->targetReflection->getPrimaryProperty()->getUnmapped();
    }

    public function load(Connection $connection, array $primaryValues)
    {
        // Remove empty primary values
        $primaryValues = array_filter(array_unique($primaryValues));
        if (empty($primaryValues)) {
            return [];
        }

        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource());

        // Set target conditions
        $filter = $this->filter;
        $filter[$this->getTargetPrimaryKey()][Filter::EQUAL] = $primaryValues;
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
        $reflection = $entity::getReflection();

        if (!$reflection->hasPrimary()) {
            throw new InvalidArgumentException(
                "Only entity with primary can save changes!"
            );
        }

        $sourceAdapter = $connection->getAdapter($this->sourceReflection->getAdapterName());

        $primaryName = $reflection->getPrimaryProperty()->getName();

        switch ($entity->getChangeType()) {
            case Entity::CHANGE_ATTACH:

                $sourceAdapter->execute(
                    $adapterQuery = $sourceAdapter->createUpdateOne(
                        $this->getSourceResource(),
                        $this->getPrimaryKey(),
                        $primaryValue,
                        [$this->getReferencingKey() => $entity->{$primaryName}]
                    )
                );
                break;
            case Entity::CHANGE_ADD:

                $targetAdapter = $connection->getAdapter(
                    $this->targetReflection->getAdapterName()
                );

                $sourceAdapter->execute(
                    $sourceAdapter->createUpdateOne(
                        $this->getSourceResource(),
                        $this->getPrimaryKey(),
                        $primaryValue,
                        [
                            $this->getReferencingKey() => $targetAdapter->execute(
                                $targetAdapter->createInsert(
                                    $this->getTargetResource(),
                                    $entity->getData(),
                                    $this->getTargetReflection()
                                        ->getPrimaryProperty()
                                        ->getUnmapped()
                                )
                            )
                        ]
                    )
                );

                break;
            case Entity::CHANGE_REMOVE:

                $targetAdapter = $connection->getAdapter(
                    $this->targetReflection->getAdapterName()
                );

                $targetAdapter->execute(
                    $targetAdapter->createDeleteOne(
                        $this->getTargetResource(),
                        $this->getTargetReflection()
                            ->getPrimaryProperty()
                            ->getUnmapped(),
                        $entity->{$primaryName}
                    )
                );

                $sourceAdapter->execute(
                    $sourceAdapter->createUpdateOne(
                        $this->getSourceResource(),
                        $this->getPrimaryKey(),
                        $primaryValue,
                        [$this->getReferencingKey() => null]
                    )
                );
                break;
            case Entity::CHANGE_DETACH:

                $sourceAdapter->execute(
                    $adapterQuery = $sourceAdapter->createUpdateOne(
                        $this->getSourceResource(),
                        $this->getPrimaryKey(),
                        $primaryValue,
                        [$this->getReferencingKey() => null]
                    )
                );
                break;
            default:
                break;
        }
    }

}