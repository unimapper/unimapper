<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Entity;
use UniMapper\Exception;
use UniMapper\Reflection;

class ManyToOne extends \UniMapper\Association
{

    public function __construct(
        $propertyName,
        Reflection\Entity $sourceReflection,
        Reflection\Entity $targetReflection,
        array $arguments
    ) {
        parent::__construct(
            $propertyName,
            $sourceReflection,
            $targetReflection,
            $arguments
        );

        if (!$targetReflection->hasPrimary()) {
            throw new Exception\DefinitionException(
                "Target entity must have defined primary when N:1 relation used!"
            );
        }

        if (!isset($arguments[0])) {
            throw new Exception\DefinitionException(
                "You must define a reference key!"
            );
        }
    }

    public function getKey()
    {
        return $this->getReferenceKey();
    }

    public function getReferenceKey()
    {
        return $this->arguments[0];
    }

    public function getTargetPrimaryKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->getTargetPrimaryKey(),
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
            [$this->getTargetPrimaryKey()]
        );
    }

    public function saveChanges($primaryValue, Connection $connection, Entity $entity)
    {
        if (!$entity->getReflection()->hasPrimary()) {
            throw new Exception\InvalidArgumentException("Only entity with primary can save changes!");
        }

        $sourceAdapter = $connection->getAdapter($this->sourceReflection->getAdapterName());

        $primaryName = $entity->getReflection()->getPrimaryProperty()->getName();

        switch ($entity->getChangeType()) {
        case Entity::CHANGE_ATTACH:

            $adapterQuery = $sourceAdapter->createUpdateOne(
                $this->getSourceResource(),
                $this->getPrimaryKey(),
                $primaryValue,
                [$this->getReferenceKey() => $entity->{$primaryName}]
            );
            $sourceAdapter->execute($adapterQuery);
            break;
        default:
            break;
        }
    }

}