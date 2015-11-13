<?php

namespace UniMapper\Query;

use UniMapper\Entity\Reflection;

class Insert extends \UniMapper\Query
{

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
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());
        $mapper = $connection->getMapper();

        $query = $adapter->createInsert(
            $this->entityReflection->getAdapterResource(),
            $mapper->unmapEntity($this->entity),
            $this->entityReflection->hasPrimary() ? $this->entityReflection->getPrimaryProperty()->getUnmapped() : null
        );

        $primaryValue = $adapter->execute($query);

        if ($this->entityReflection->hasPrimary()) {

            $t = $mapper->mapValue(
                $this->entityReflection->getPrimaryProperty(),
                $primaryValue
            );
            return $t;
        }
    }

}