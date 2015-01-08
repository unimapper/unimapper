<?php

namespace UniMapper\Query;

class Count extends Conditionable
{

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $this->getAdapter($connection);
        $query = $adapter->createCount(
            $this->entityReflection->getAdapterResource()
        );
        if ($this->conditions) {
            $query->setConditions($this->unmapConditions($connection->getMapper(), $this->conditions));
        }
        return (int) $adapter->execute($query);
    }

}