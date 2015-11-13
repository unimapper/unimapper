<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class UpdateOne extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    protected $entity;

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection $entityReflection,
        $primaryValue,
        array $data
    ) {
        parent::__construct($entityReflection);

        $this->primaryValue = $primaryValue;

        // Primary value update is not allowed
        if (!$entityReflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Entity '" . $entityReflection->getClassName() . "' has no "
                . "primary property!"
            );
        }

        // Do not change primary value
        unset($data[$entityReflection->getPrimaryProperty()->getName()]);

        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());
        $mapper = $connection->getMapper();

        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $query = $adapter->createUpdateOne(
            $this->entityReflection->getAdapterResource(),
            $this->entityReflection->getPrimaryProperty()->getUnmapped(),
            $mapper->unmapValue(
                $this->entityReflection->getPrimaryProperty(),
                $this->primaryValue
            ),
            $values
        );

        return (bool) $adapter->execute($query);
    }

}