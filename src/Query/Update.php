<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class Update extends \UniMapper\Query
{

    use Filterable;
    use Limit;

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection $reflection,
        array $data
    ) {
        parent::__construct($reflection);
        $this->entity = $reflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $mapper = $connection->getMapper();
        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $adapter = $connection->getAdapter($this->reflection->getAdapterName());

        $query = $adapter->createUpdate(
            $this->reflection->getAdapterResource(),
            $values
        );
        if ($this->filter) {
            $query->setFilter(
                $mapper->unmapFilter(
                    $this->reflection,
                    $this->filter
                )
            );
        }

        return (int) $adapter->execute($query);
    }

}