<?php

namespace UniMapper\Association;

use UniMapper\Connection;
use UniMapper\Exception;
use UniMapper\Reflection;

class OneToMany extends \UniMapper\Association
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

        if (!isset($arguments[0])) {
            throw new Exception\AssociationException(
                "You must define referenced key!"
            );
        }
    }

    public function getReferencedKey()
    {
        return $this->arguments[0];
    }

    public function load(Connection $connection, array $primaryValues)
    {
        $targetAdapter = $connection->getAdapter($this->targetReflection->getAdapterName());

        $query = $targetAdapter->createSelect($this->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->getReferencedKey(),
                    "IN",
                    array_keys($primaryValues),
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (!$result) {
            return [];
        }

        return $result;
    }

}