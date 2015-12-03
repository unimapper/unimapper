<?php

namespace UniMapper\Association;

use UniMapper\Adapter;
use UniMapper\Connection;
use UniMapper\Exception;
use UniMapper\Entity;
use UniMapper\Mapper;

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
        $changes = array_filter($collection->getChanges());
        if (empty($changes)) {
            return;
        }
        $changes = $collection->getChanges();

        $targetAdapter = $connection->getAdapter(
            $this->targetReflection->getAdapterName()
        );

        $mapper = $connection->getMapper();

        // Delete removed entities
        if ($changes[Entity::CHANGE_REMOVE]) {
            $this->_saveRemove(
                $targetAdapter,
                $mapper,
                $changes[Entity::CHANGE_REMOVE]
            );
        }

        // Detach entities
        if ($changes[Entity::CHANGE_DETACH]) {
            $this->_saveDetach(
                $targetAdapter,
                $mapper,
                $changes[Entity::CHANGE_DETACH]
            );
        }

        // Unmap primary value
        $primaryValue = $mapper->unmapValue(
            $this->sourceReflection->getPrimaryProperty(),
            $primaryValue
        );

        // Add entities
        $this->_saveAdd(
            $targetAdapter,
            $mapper,
            $changes[Entity::CHANGE_ADD],
            $primaryValue
        );

        // Attach entities
        if ($changes[Entity::CHANGE_ATTACH]) {
            $this->_saveAttach(
                $targetAdapter,
                $mapper,
                $changes[Entity::CHANGE_ATTACH],
                $primaryValue
            );
        }
    }

    private function _saveRemove(Adapter $adapter, Mapper $mapper, array $keys)
    {
        $adapterQuery = $adapter->createDelete(
            $this->targetReflection->getAdapterResource()
        );

        $adapterQuery->setFilter(
            [
                $this->getReferencedKey() => [
                    Entity\Filter::EQUAL => $this->_unmapKeys($mapper, $keys)
                ]
            ]
        );
        $adapter->execute($adapterQuery);
    }

    private function _saveDetach(Adapter $adapter, Mapper $mapper, array $keys)
    {
        $adapterQuery = $adapter->createUpdate(
            $this->getTargetResource(),
            [$this->getReferencedKey() => null]
        );
        $adapterQuery->setFilter(
            [
                $this->getReferencedKey() => [
                    Entity\Filter::EQUAL => $this->_unmapKeys($mapper, $keys)
                ]
            ]
        );
        $adapter->execute($adapterQuery);
    }

    private function _saveAdd(
        Adapter $adapter,
        Mapper $mapper,
        array $entities,
        $primaryValue
    ) {
        foreach ($entities as $entity) {

            $adapter->execute(
                $adapter->createInsert(
                    $this->targetReflection->getAdapterResource(),
                    $mapper->unmapEntity($entity) + [$this->getReferencedKey() => $primaryValue],
                    $this->targetReflection->getPrimaryProperty()->getUnmapped()
                )
            );
        }
    }

    private function _saveAttach(
        Adapter $adapter,
        Mapper $mapper,
        array $keys,
        $primaryValue
    ) {
        $adapterQuery = $adapter->createUpdate(
            $this->getTargetResource(),
            [$this->getReferencedKey() => $primaryValue]
        );
        $adapterQuery->setFilter(
            [
                $this->getReferencedKey() => [
                    Entity\Filter::EQUAL => $this->_unmapKeys($mapper, $keys)
                ]
            ]
        );
        $adapter->execute($adapterQuery);
    }

    private function _unmapKeys(Mapper $mapper, array $keys)
    {
        return array_map(function ($val) use ($mapper) {

            return $mapper->unmapValue(
                $this->targetReflection->getPrimaryProperty(),
                $val
            );
        }, $keys);
    }

}