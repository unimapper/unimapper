<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        array $data
    ) {
        parent::__construct($entityReflection, $adapters);
        $this->entity = $entityReflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Adapter\IAdapter $adapter)
    {
        $query = $adapter->createInsert(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $adapter->getMapper()->unmapEntity($this->entity)
        );

        $primaryValue = $adapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        if ($this->entityReflection->hasPrimaryProperty()) {

            return $adapter->getMapper()->mapValue(
                $this->entityReflection->getPrimaryProperty(),
                $primaryValue
            );
        }
    }

}