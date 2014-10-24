<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class DeleteOne extends \UniMapper\Query
{

    /** @var mixed */
    public $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        $primaryValue
    ) {
        parent::__construct($entityReflection, $adapters);

        if (!$entityReflection->hasPrimaryProperty()) {
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
            $this->entityReflection->getAdapterReflection()->getResource(),
            $primaryProperty->getName(true),
            $this->primaryValue
        );

        $success = (bool) $adapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        return $success;
    }

}