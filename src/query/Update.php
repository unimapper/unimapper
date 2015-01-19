<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class Update extends \UniMapper\Query
{

    use Conditionable;

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection\Entity $entityReflection,
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

        if (!$this->conditions) {
            throw new Exception\QueryException("At least one condition must be set!");
        }

        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $query = $adapter->createUpdate(
            $this->entityReflection->getAdapterResource(),
            $values
        );
        if ($this->conditions) {
            $query->setConditions($this->unmapConditions($mapper, $this->conditions));
        }

        return (int) $adapter->execute($query);
    }

}