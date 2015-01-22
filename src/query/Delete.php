<?php

namespace UniMapper\Query;

use UniMapper\Exception;

class Delete extends \UniMapper\Query
{

    use Conditionable;
    use Limit;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        if (!$this->conditions) {
            throw new Exception\QueryException(
                "At least one condition must be set!"
            );
        }

        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createDelete(
            $this->entityReflection->getAdapterResource()
        );
        $query->setConditions($this->unmapConditions($connection->getMapper(), $this->conditions));

        return (int) $adapter->execute($query);
    }

}