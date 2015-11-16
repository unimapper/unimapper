<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class DeleteOne extends \UniMapper\Query
{

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection $reflection,
        $primaryValue
    ) {
        parent::__construct($reflection);

        if (!$reflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use deleteOne() on entity without primary property!"
            );
        }

        if (empty($primaryValue)) {
            throw new Exception\QueryException(
                "Primary value can not be empty!"
            );
        }

        try {
            $reflection->getPrimaryProperty()->validateValueType($primaryValue);
        } catch (Exception\InvalidArgumentException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->primaryValue = $primaryValue;
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());

        $primaryProperty = $this->reflection->getPrimaryProperty();

        $query = $adapter->createDeleteOne(
            $this->reflection->getAdapterResource(),
            $primaryProperty->getUnmapped(),
            $connection->getMapper()->unmapValue(
                $primaryProperty,
                $this->primaryValue
            )
        );

        return (bool) $adapter->execute($query);
    }

}