<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Mapper,
    UniMapper\Reflection;

class Update extends Conditionable
{

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        Mapper $mapper,
        array $data
    ) {
        parent::__construct($entityReflection, $adapters, $mapper);
        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $values = $this->mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        if (count($this->conditions) === 0) {
            throw new Exception\QueryException("At least one condition must be set!");
        }

        $query = $adapter->createUpdate(
            $this->entityReflection->getAdapterResource(),
            $values
        );
        $query->setConditions($this->conditions);

        return (int) $adapter->execute($query);
    }

}