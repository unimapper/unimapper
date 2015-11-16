<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query
{

    use Filterable;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());
        $query = $adapter->createCount(
            $this->reflection->getAdapterResource()
        );
        if ($this->filter) {
            $query->setFilter(
                $connection->getMapper()->unmapFilter(
                    $this->reflection,
                    $this->filter
                )
            );
        }
        return (int) $adapter->execute($query);
    }

}