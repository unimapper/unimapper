<?php

namespace UniMapper\Query;

use UniMapper\Entity\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    protected $entity;

    public function __construct(
        Reflection $reflection,
        array $data
    ) {
        parent::__construct($reflection);
        $this->entity = $reflection->createEntity($data);
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->reflection->getAdapterName());
        $mapper = $connection->getMapper();

        $query = $adapter->createInsert(
            $this->reflection->getAdapterResource(),
            $mapper->unmapEntity($this->entity),
            $this->reflection->hasPrimary() ? $this->reflection->getPrimaryProperty()->getUnmapped() : null
        );

        $primaryValue = $adapter->execute($query);

        if ($this->reflection->hasPrimary()) {

            $t = $mapper->mapValue(
                $this->reflection->getPrimaryProperty(),
                $primaryValue
            );
            return $t;
        }
    }

}