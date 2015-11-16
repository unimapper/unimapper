<?php

namespace UniMapper\Query;

class Delete extends \UniMapper\Query
{

    use Filterable;
    use Limit;

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());

        $query = $adapter->createDelete(
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