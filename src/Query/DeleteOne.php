<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class DeleteOne extends \UniMapper\Query
{

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection $entityReflection,
        $primaryValue
    ) {
        parent::__construct($entityReflection);

        if (!$entityReflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use deleteOne() on entity without primary property!"
            );
        }

        if (empty($primaryValue)) {
            throw new Exception\QueryException(
                "Primary value can not be empty!"
            );
        }

        $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);

        $this->primaryValue = $primaryValue;
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createDeleteOne(
            $this->entityReflection->getAdapterResource(),
            $primaryProperty->getName(true),
            $connection->getMapper()->unmapValue(
                $primaryProperty,
                $this->primaryValue
            )
        );

        return (bool) $adapter->execute($query);
    }

}