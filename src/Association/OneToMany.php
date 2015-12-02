<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Exception;
use UniMapper\Entity;

class OneToMany extends Multi
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

        if (!isset($mapBy[0])) {
            throw new Exception\AssociationException(
                "You must define referenced key!"
            );
        }
    }

    public function getReferencedKey()
    {
        return $this->mapBy[0];
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect(
            $this->getTargetResource(),
            [],
            $this->orderBy,
            $this->limit,
            $this->offset
        );

        // Set target conditions
        $filter = $this->filter;
        $filter[$this->getReferencedKey()][Entity\Filter::EQUAL] = array_values($primaryValues);
        $query->setFilter($filter);

        $result = $targetAdapter->execute($query);

        if (!$result) {
            return [];
        }

        $return = [];
        foreach ($result as $row) {
            $return[$row[$this->getReferencedKey()]][] = $row;
        }

        return $return;
    }


    /**
     * Save changes in target collection
     *
     * @param string            $primaryValue Primary value from source entity
     * @param Connection        $connection
     * @param Entity\Collection $collection   Target collection
     */
    public function saveChanges(
        $primaryValue,
        Connection $connection,
        Entity\Collection $collection
    ) {
        $changes = $collection->getChanges();
        if (empty(array_filter($changes))) {
            return;
        }

        $targetAdapter = $connection->getAdapter(
            $this->targetReflection->getAdapterName()
        );

        // Delete removed entities
        if ($changes[Entity::CHANGE_REMOVE]) {

            $adapterQuery = $targetAdapter->createDelete(
                $this->targetReflection->getAdapterResource(),
                $this->targetReflection->getPrimaryProperty()->getUnmapped()
            );
            $adapterQuery->setFilter(
                [
                    $this->getReferencedKey() => [
                        Entity\Filter::EQUAL => $changes[Entity::CHANGE_REMOVE]
                    ]
                ]
            );
            $targetAdapter->execute($adapterQuery);
        }

        // Detach entities
        $keys = $changes[Entity::CHANGE_DETACH];
        if ($keys) {

            $adapterQuery = $targetAdapter->createUpdate(
                $this->getTargetResource(),
                [$this->getReferencedKey() => null]
            );
            $adapterQuery->setFilter(
                [
                    $this->getReferencedKey() => [
                        Entity\Filter::EQUAL => $keys
                    ]
                ]
            );
            $targetAdapter->execute($adapterQuery);
        }

        // Add entities
        foreach ($changes[Entity::CHANGE_ADD] as $entity) {

            $targetAdapter->execute(
                $targetAdapter->createInsert(
                    $this->targetReflection->getAdapterResource(),
                    $entity->getData() + [$this->getReferencedKey() => $primaryValue],
                    $this->targetReflection->getPrimaryProperty()->getUnmapped()
                )
            );
        }

        // Attach entities
        $keys = $changes[Entity::CHANGE_ATTACH];
        if ($keys ) {

            $adapterQuery = $targetAdapter->createUpdate(
                $this->getTargetResource(),
                [$this->getReferencedKey() => $primaryValue]
            );
            $adapterQuery->setFilter(
                [
                    $this->getReferencedKey() => [
                        Entity\Filter::EQUAL => $keys
                    ]
                ]
            );
            $targetAdapter->execute($adapterQuery);
        }
    }

}