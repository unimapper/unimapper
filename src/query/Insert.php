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

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $values = $adapter->getMapping()->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new Exception\QueryException("Nothing to insert!");
        }

        // Prevent to set empty primary property
        if ($this->entityReflection->hasPrimaryProperty()) {

            $primaryName = $this->entityReflection->getPrimaryProperty()
                ->getMappedName();

            if (empty($values[$primaryName])) {
                unset($values[$primaryName]);
            }
        }

        $primaryValue = $adapter->insert(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $values
        );

        if ($this->entityReflection->hasPrimaryProperty()) {

            if ($primaryValue === null) {
                throw new Exception\QueryException(
                    "Insert should return primary value but null given!"
                );
            }
            return $adapter->getMapping()->mapValue(
                $this->entityReflection->getPrimaryProperty(),
                $primaryValue
            );
        }
    }

}