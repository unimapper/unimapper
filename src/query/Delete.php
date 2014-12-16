<?php

namespace UniMapper\Query;

use UniMapper\Exception;

class Delete extends Conditionable
{

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        if (count($this->conditions) === 0) {
            throw new Exception\QueryException(
                "At least one condition must be set!"
            );
        }

        $query = $adapter->createDelete(
            $this->entityReflection->getAdapterResource()
        );
        $query->setConditions($this->conditions);

        return (int) $adapter->execute($query);
    }

}