<?php

namespace UniMapper\Query;

use UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

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
        $adapter = $this->getAdapter($connection);
        $mapper = $connection->getMapper();

        $query = $adapter->createInsert(
            $this->entityReflection->getAdapterResource(),
            $mapper->unmapEntity($this->entity)
        );

        $primaryValue = $adapter->execute($query);

        if ($this->entityReflection->hasPrimary()) {

            return $mapper->mapValue(
                $this->entityReflection->getPrimaryProperty(),
                $primaryValue
            );
        }
    }

}