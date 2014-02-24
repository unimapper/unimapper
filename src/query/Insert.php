<?php

namespace UniMapper\Query;

use UniMapper\Entity,
    UniMapper\Reflection\EntityReflection,
    UniMapper\Exceptions\QueryException;

/**
 * Insert query object
 */
class Insert extends \UniMapper\Query
{

    public $entity;

    public function __construct(EntityReflection $entityReflection, array $mappers, Entity $entity)
    {
        parent::__construct($entityReflection, $mappers);

        $requiredClass = $this->entityReflection->getName();
        if (!$entity instanceof $requiredClass) {
            throw new QueryException("Inserted entity must be instance of " . $requiredClass . " but " . $entity->getReflection()->getName() . "given!");
        }

        $this->entity = $entity;
    }

    public function onExecute()
    {
        if ($this->entityReflection->isHybrid()) {
            return $this->insertHybrid();
        }

        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            $mapper = $this->mappers[$mapperName];
            $result = $mapper->insert($this);

            $primaryProperty = $this->entityReflection->getPrimaryProperty();
            if ($primaryProperty !== null) {
                $this->entity->{$primaryProperty->getName()} = $result;
            }

            // Make entity active
            $this->entity->addMapper($mapper);

            return $this->entity;
        }
    }

    private function insertHybrid()
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Primary property required for hybrid entity " . $this->entityReflection->getName() . "!");
        }

        $primaryValue = $this->entity->{$primaryProperty->getName()};
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            $mapper = $this->mappers[$mapperName];

            $result = $mapper->insert($this);
            if ($primaryValue === null) {
                $this->entity->{$primaryProperty->getName()} = $primaryValue = $result; // Set primary value automatically for all next mappers
            } elseif ($result !== $primaryValue) {
                throw new QueryException(
                    "Primary value " . $result . " from mapper " . $mapperName . " is not equal to primary value " . $primaryValue . " from the first mapper!"
                );
            }

            // Make entity active
            $this->entity->addMapper($mapper);
        }
        $this->entity->{$primaryProperty->getName()} = $primaryValue;
        return $this->entity;
    }

}