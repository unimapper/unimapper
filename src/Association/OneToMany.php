<?php

namespace UniMapper\Association;

use UniMapper\Adapter;
use UniMapper\Association;
use UniMapper\Connection;
use UniMapper\Exception\AssociationException;
use UniMapper\Entity;
use UniMapper\Entity\Collection;
use UniMapper\Entity\Filter;
use UniMapper\Entity\Reflection;
use UniMapper\Mapper;

class OneToMany extends Association
{

    /** @var string */
    private $referencedKey;

    public function __construct(
        Reflection $sourceReflection,
        Reflection $targetReflection,
        $referencedKey = null
    ) {
        parent::__construct($sourceReflection, $targetReflection);

        if (!$referencedKey) {

            $referencedKey = $sourceReflection->getAdapterResource()
                . self::JOINER
                . $sourceReflection->getPrimaryProperty()->getUnmapped();
        }

        $this->referencedKey = $referencedKey;
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter(
            $this->targetReflection->getAdapterName()
        );

        $query = $targetAdapter->createSelect(
            $this->targetReflection->getAdapterResource()
        );

        $query->setFilter(
            [
                $this->referencedKey => [
                    Entity\Filter::EQUAL => array_values($primaryValues)
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (!$result) {
            return [];
        }

        $return = [];
        foreach ($result as $row) {
            $return[$row[$this->referencedKey]][] = $row;
        }

        return $return;
    }

    /**
     * Save changes in target collection
     *
     * @param string     $primaryValue Primary value from source entity
     * @param Connection $connection
     * @param Collection $collection   Target collection
     *
     * @throws AssociationException
     */
    public function saveChanges(
        $primaryValue,
        Connection $connection,
        Collection $collection
    ) {
        if ($collection->getEntityClass() !== $this->targetReflection->getClassName()) {
            throw new AssociationException(
                "Input collection should be type of "
                . $this->targetReflection->getClassName()
                . " but type of " . $collection->getEntityClass() . " given!"
            );
        }

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
                $this->referencedKey => [
                    Filter::EQUAL => $this->_unmapKeys($mapper, $keys)
                ]
            ]
        );
        $adapter->execute($adapterQuery);
    }

    private function _saveDetach(Adapter $adapter, Mapper $mapper, array $keys)
    {
        $adapterQuery = $adapter->createUpdate(
            $this->targetReflection->getAdapterResource(),
            [$this->referencedKey => null]
        );
        $adapterQuery->setFilter(
            [
                $this->referencedKey => [
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
                    $mapper->unmapEntity($entity) + [$this->referencedKey => $primaryValue],
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
            $this->targetReflection->getAdapterResource(),
            [$this->referencedKey => $primaryValue]
        );
        $adapterQuery->setFilter(
            [
                $this->referencedKey => [
                    Filter::EQUAL => $this->_unmapKeys($mapper, $keys)
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