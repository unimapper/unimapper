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

        if (!$entity->getChangeType()) {
            return;
        }

        $sourceAdapter = $connection->getAdapter(
            $this->sourceReflection->getAdapterName()
        );

        $primaryProperty = $reflection->getPrimaryProperty();
        $primaryName = $primaryProperty->getName();

        $mapper = $connection->getMapper();

        // Unmap primary value
        $primaryValue = $mapper->unmapValue(
            $this->sourceReflection->getPrimaryProperty(),
            $primaryValue
        );

        switch ($entity->getChangeType()) {

            case Entity::CHANGE_ATTACH:

                $sourceAdapter->execute(
                    $adapterQuery = $sourceAdapter->createUpdateOne(
                        $this->getSourceResource(),
                        $this->getPrimaryKey(),
                        $primaryValue,
                        [
                            $this->getReferencingKey() => $mapper->unmapValue(
                                $primaryProperty,
                                $entity->{$primaryName}
                            )
                        ]
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
                                    $mapper->unmapEntity($entity),
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
                        $mapper->unmapValue(
                            $primaryProperty,
                            $entity->{$primaryName}
                        )
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