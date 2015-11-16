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
        Reflection $reflection,
        $primaryValue,
        array $data
    ) {
        parent::__construct($reflection);

        $this->primaryValue = $primaryValue;

        // Primary value update is not allowed
        if (!$reflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Entity '" . $reflection->getClassName() . "' has no "
                . "primary property!"
            );
        }

        // Do not change primary value
        unset($data[$reflection->getPrimaryProperty()->getName()]);

        $this->entity = $reflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());
        $mapper = $connection->getMapper();

        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to update!");
        }

        $query = $adapter->createUpdateOne(
            $this->reflection->getAdapterResource(),
            $this->reflection->getPrimaryProperty()->getUnmapped(),
            $mapper->unmapValue(
                $this->reflection->getPrimaryProperty(),
                $this->primaryValue
            ),
            $values
        );

        return (bool) $adapter->execute($query);
    }

}