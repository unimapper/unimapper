<?php

namespace UniMapper\Query;

class Delete extends \UniMapper\Query
{

    use Filterable;
    use Limit;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createDelete(
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