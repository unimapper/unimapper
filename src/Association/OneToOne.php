<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Exception;
use UniMapper\Entity;

class OneToOne extends Single
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
                "You must define referencing key!"
            );
        }

        if (!$targetReflection->hasPrimary()) {
            throw new Exception\AssociationException(
                "Target entity must have defined primary when 1:1 relation used!"
            );
        }
    }

    public function getReferencingKey()
    {
        return $this->mapBy[0];
    }

    public function getKey()
    {
        return $this->getReferencingKey();
    }

    public function getTargetPrimaryKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource());

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

        return $this->groupResult($result, [$this->getTargetPrimaryKey()]);
    }

}