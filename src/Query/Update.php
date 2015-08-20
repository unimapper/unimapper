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
        Reflection $entityReflection,
        array $data
    ) {
        parent::__construct($entityReflection);
        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $mapper = $connection->getMapper();
        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createUpdate(
            $this->entityReflection->getAdapterResource(),
            $values
        );
        if ($this->filter) {
            $query->setFilter(
                $mapper->unmapFilter(
                    $this->entityReflection,
                    $this->filter
                )
            );
        }

        return (int) $adapter->execute($query);
    }

}