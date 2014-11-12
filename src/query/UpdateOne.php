<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class UpdateOne extends Conditionable
{

    /** @var \UniMapper\Entity */
    protected $entity;

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        $primaryValue,
        array $data
    ) {
        parent::__construct($entityReflection, $adapters);

        $this->primaryValue = $primaryValue;

        // Primary value update is not allowed
        if (!$entityReflection->hasPrimaryProperty()) {
            throw new Exception\QueryException(
                "Entity '" . $entityReflection->getClassName() . "' has no "
                . "primary property!"
            );
        }

        // Do not change primary value
        unset($data[$entityReflection->getPrimaryProperty()->getName()]);

        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $values = $adapter->createMapping()->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $query = $adapter->createUpdateOne(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $this->entityReflection->getPrimaryProperty()->getName(true),
            $this->primaryValue,
            $values
        );

        $success = (bool) $adapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        return $success;
    }

}