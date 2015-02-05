<?php

namespace UniMapper\Query;

class Delete extends \UniMapper\Query
{

    use Conditionable;
    use Limit;

    protected function onExecute(\UniMapper\Connection $connection)
    {
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