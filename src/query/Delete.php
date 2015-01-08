<?php

namespace UniMapper\Query;

use UniMapper\Exception;

class Delete extends Conditionable
{

    protected function onExecute(\UniMapper\Connection $connection)
    {
        if (count($this->conditions) === 0) {
            throw new Exception\QueryException(
                "At least one condition must be set!"
            );
        }

        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createDelete(
            $this->entityReflection->getAdapterResource()
        );
        if ($this->conditions) {
            $query->setConditions($this->unmapConditions($connection->getMapper(), $this->conditions));
        }

        return (int) $adapter->execute($query);
    }

}