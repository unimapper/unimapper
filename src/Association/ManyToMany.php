<?php

namespace UniMapper\Association;

use UniMapper\Adapter;
use UniMapper\Association;
use UniMapper\Connection;
use UniMapper\Entity;
use UniMapper\Entity\Collection;
use UniMapper\Entity\Filter;
use UniMapper\Exception\AssociationException;
use UniMapper\Mapper;

class ManyToMany extends Association
{

    /** @var string */
    private $joinKey;

    /** @var string */
    private $joinResource;

    /** @var string */
    private $referencingKey;

    /** @var bool */
    private $dominant = true;

    public function __construct(
        Entity\Reflection $sourceReflection,
        Entity\Reflection $targetReflection,
        array $arguments = [],
        $dominant = true
    ) {
        parent::__construct($sourceReflection, $targetReflection);

        if (!$targetReflection->hasPrimary()) {
            throw new AssociationException(
                "Target entity must have defined primary when M:N relation used!"
            );
        }

        // Auto-detection
        if (!isset($arguments[0])) {

            $arguments[0] = $sourceReflection->getAdapterResource()
                . self::JOINER
                . $sourceReflection->getPrimaryProperty()->getUnmapped();
        }
        if (!isset($arguments[1])) {

            $firstRes = $sourceReflection->getAdapterResource();
            $secondRes = $targetReflection->getAdapterResource();
            if (strnatcasecmp($firstRes, $secondRes) > 0) {
                list($secondRes, $firstRes) = [$firstRes, $secondRes];
            }
            $arguments[1] = $firstRes . self::JOINER . $secondRes;
        }
        if (!isset($arguments[2])) {

            $arguments[2] = $targetReflection->getAdapterResource()
                . self::JOINER
                . $targetReflection->getPrimaryProperty()->getUnmapped();
        }

        $this->joinKey = $arguments[0];
        $this->joinResource = $arguments[1];
        $this->referencingKey = $arguments[2];
        $this->dominant = (bool) $dominant;
    }

    /**
     * @param Connection $connection
     * @param array $primaryValues
     *
     * @return array
     *
     * @throws AssociationException
     * @throws \Exception
     * @throws \UniMapper\Exception\ConnectionException
     *
     * @todo should be optimized with 1 query only on same adapters
     */
    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        if ($this->dominant) {

            $currentAdapter = $connection->getAdapter(
                $this->sourceReflection->getAdapterName()
            );
        } else{
            $currentAdapter = $targetAdapter;
        }

        $joinQuery = $currentAdapter->createSelect(
            $this->joinResource,
            [$this->joinKey, $this->referencingKey]
        );
        $joinQuery->setFilter(
            [$this->joinKey => [Filter::EQUAL => $primaryValues]]
        );

        $joinResult = $currentAdapter->execute($joinQuery);

        if (!$joinResult) {
            return [];
        }

        $joinResult = Association::groupResult(
            $joinResult,
            [$this->referencingKey, $this->joinKey]
        );

        $targetQuery = $targetAdapter->createSelect(
            $this->targetReflection->getAdapterResource()
        );
        $targetQuery->setFilter(
            [
                $this->targetReflection->getPrimaryProperty()->getUnmapped() => [
                    Entity\Filter::EQUAL => array_keys($joinResult)
                ]
            ]
        );

        $targetResult = $targetAdapter->execute($targetQuery);
        if (!$targetResult) {
            return [];
        }

        $targetResult = Association::groupResult(
            $targetResult,
            [$this->targetReflection->getPrimaryProperty()->getUnmapped()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new AssociationException(
                        "Can not merge associated result key '" . $targetKey
                        . "' not found in result from '"
                        . $this->targetReflection->getAdapterResource()
                        . "'! Maybe wrong value in join resource."
                    );
                }
                $result[$originKey][] = $targetResult[$targetKey];
            }
        }

        return $result;
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
    public function saveChanges($primaryValue, Connection $connection, Collection $collection)
    {
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

        $sourceAdapter = $connection->getAdapter($this->sourceReflection->getAdapterName());
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());
        $mapper = $connection->getMapper();

        if (!$this->dominant) {
            $sourceAdapter = $targetAdapter;
        }

        // Unmap primary value
        $primaryValue = $mapper->unmapValue(
            $this->sourceReflection->getPrimaryProperty(),
            $primaryValue
        );

        $this->_saveAdd(
            $primaryValue,
            $sourceAdapter,
            $targetAdapter,
            $collection->getChanges(),
            $mapper
        );

        $this->_saveRemove(
            $primaryValue,
            $sourceAdapter,
            $targetAdapter,
            $collection->getChanges(),
            $mapper
        );
    }

    private function _saveRemove(
        $primaryValue,
        Adapter $joinAdapter,
        Adapter $targetAdapter,
        array $changes,
        Mapper $mapper
    ) {
        if ($changes[Entity::CHANGE_REMOVE]) {

            $adapterQuery = $targetAdapter->createDelete(
                $this->targetReflection->getAdapterResource()
            );
            $adapterQuery->setFilter(
                $mapper->unmapFilter(
                    $this->targetReflection,
                    [
                        $this->targetReflection->getPrimaryProperty()->getName() => [
                            Filter::EQUAL => $changes[Entity::CHANGE_REMOVE]
                        ]
                    ]
                )
            );
            $targetAdapter->execute($adapterQuery);
        }

        $assocKeys = $this->_unmapAssocKeys(
            $mapper,
            $changes[Entity::CHANGE_DETACH] + $changes[Entity::CHANGE_REMOVE]
        );

        if ($assocKeys) {

            $adapterQuery = $joinAdapter->createManyToManyRemove(
                $this->sourceReflection->getAdapterResource(),
                $this->joinResource,
                $this->targetReflection->getAdapterResource(),
                $this->joinKey,
                $this->referencingKey,
                $primaryValue,
                array_unique($assocKeys)
            );
            $joinAdapter->execute($adapterQuery);
        }
    }

    private function _saveAdd(
        $primaryValue,
        Adapter $joinAdapter,
        Adapter $targetAdapter,
        array $changes,
        Mapper $mapper
    ) {
        $assocKeys = $changes[Entity::CHANGE_ATTACH];
        foreach ($changes[Entity::CHANGE_ADD] as $entity) {

            $assocKeys[] = $targetAdapter->execute(
                $targetAdapter->createInsert(
                    $this->targetReflection->getAdapterResource(),
                    $mapper->unmapEntity($entity),
                    $this->targetReflection->getPrimaryProperty()->getUnmapped()
                )
            );
        }

        $assocKeys = $this->_unmapAssocKeys($mapper, $assocKeys);
        if ($assocKeys) {

            $adapterQuery = $joinAdapter->createManyToManyAdd(
                $this->sourceReflection->getAdapterResource(),
                $this->joinResource,
                $this->targetReflection->getAdapterResource(),
                $this->joinKey,
                $this->referencingKey,
                $primaryValue,
                array_unique($assocKeys)
            );
            $joinAdapter->execute($adapterQuery);
        }
    }

    private function _unmapAssocKeys(Mapper $mapper, array $keys)
    {
        return array_map(function ($val) use ($mapper) {

            return $mapper->unmapValue(
                $this->targetReflection->getPrimaryProperty(),
                $val
            );
        }, $keys);
    }

}