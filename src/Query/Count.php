<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query
{

    use Filterable;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());
        $query = $adapter->createCount(
            $this->entityReflection->getAdapterResource()
        );
        if ($this->filter) {
            $query->setFilter(
                $connection->getMapper()->unmapFilter(
                    $this->entityReflection,
                    $this->filter
                )
            );
        }
        return (int) $adapter->execute($query);
    }

}