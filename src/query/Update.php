<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class Update extends Conditionable
{

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        array $data
    ) {
        parent::__construct($entityReflection, $adapters);

        // Primary value update is not allowed
        if ($entityReflection->hasPrimaryProperty()) {

            $primaryName = $entityReflection->getPrimaryProperty()->getMappedName();
            if (isset($data[$primaryName])) {
                throw new Exception\QueryException(
                    "Update is not allowed on primary property '"
                    .  $primaryName . "'!"
                );
            }
        }

        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $values = $adapter->getMapping()->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        if (count($this->conditions) === 0) {
            throw new Exception\QueryException("At least one condition must be set!");
        }

        $mapping = $adapter->getMapping();

        $adapter->update(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $values,
            $mapping->unmapConditions($this->conditions, $this->entityReflection)
        );
    }

}