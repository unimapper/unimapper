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
        $conditions = $this->conditions;
        $conditions[] = [
            $this->getReferencedKey(),
            "IN",
            array_values($primaryValues),
            "AND"
        ];
        $query->setConditions($conditions);

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

}