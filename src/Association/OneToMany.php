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
            $this->getTargetSelection(),
            $this->orderBy,
            $this->limit,
            $this->offset
        );

        // Set target conditions
        $filter = $this->filter;
        $filter[$this->getReferencedKey()][Entity\Filter::EQUAL] = array_values($primaryValues);
        if ($this->getTargetFilter()) {
            $filter = array_merge(
                $connection->getMapper()->unmapFilter($this->getTargetReflection(), $this->getTargetFilter()),
                $filter
            );
        }
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

}