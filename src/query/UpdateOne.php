<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Query\IConditionable,
    UniMapper\Reflection;

class UpdateOne extends \UniMapper\Query implements IConditionable
{

    /** @var \UniMapper\Entity */
    private $entity;

    private $primaryValue;

    public function __construct(Reflection\Entity $entityReflection,
        array $adapters, $primaryValue, array $data
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

    public function getValues()
    {
        return $this->values;
    }

    public function getPrimaryValue()
    {
        return $this->primaryValue;
    }

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        $values = $adapter->getMapping()->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $adapter->updateOne(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $this->entityReflection->getPrimaryProperty()->getMappedName(),
            $this->primaryValue,
            $values
        );
    }

}
