<?php

namespace UniMapper\Query;

use UniMapper\Reflection,
    UniMapper\Mapper;

class Insert extends \UniMapper\Query
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
        $query = $adapter->createInsert(
            $this->entityReflection->getAdapterResource(),
            $this->mapper->unmapEntity($this->entity)
        );

        $primaryValue = $adapter->execute($query);

        if ($this->entityReflection->hasPrimary()) {

            return $this->mapper->mapValue(
                $this->entityReflection->getPrimaryProperty(),
                $primaryValue
            );
        }
    }

}