<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Exception;
use UniMapper\Reflection;

class OneToOne extends OneToMany
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
                "Target entity must have defined primary when 1:1 relation used!"
            );
        }
    }

    public function getKey()
    {
        return $this->getForeignKey();
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

        return $this->groupResult($result, [$this->getTargetPrimaryKey()]);
    }

}