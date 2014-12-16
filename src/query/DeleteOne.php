<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Mapper,
    UniMapper\Reflection;

class DeleteOne extends \UniMapper\Query
{

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        Mapper $mapper,
        $primaryValue
    ) {
        parent::__construct($entityReflection, $adapters, $mapper);

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

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createDeleteOne(
            $this->entityReflection->getAdapterResource(),
            $primaryProperty->getName(true),
            $this->primaryValue
        );

        return (bool) $adapter->execute($query);
    }

}