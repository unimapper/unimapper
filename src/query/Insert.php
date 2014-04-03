<?php

namespace UniMapper\Query;

use UniMapper\Entity,
    UniMapper\Reflection,
    UniMapper\Exceptions\QueryException;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    public $entity;

    /** @var boolean */
    public $returnPrimaryValue = true;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, Entity $entity)
    {
        parent::__construct($entityReflection, $mappers);

        $requiredClass = $this->entityReflection->getName();
        if (!$entity instanceof $requiredClass) {
            throw new QueryException("Inserted entity must be instance of " . $requiredClass . " but " . get_class($entity) . "given!");
        }

        $this->entity = $entity;
    }

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        $result = $mapper->insert($this);

        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty !== null) {
            $this->entity->{$primaryProperty->getName()} = $result;
        }

        return $this->entity;
    }

    public function executeHybrid()
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Primary property required for hybrid entity " . $this->entityReflection->getName() . "!");
        }

        $primaryValue = $this->entity->{$primaryProperty->getName()};
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            if ($primaryValue) {
                $this->returnPrimaryValue = false;
            }

            $insertedPrimaryValue = $this->mappers[$mapperName]->insert($this);
            if ($insertedPrimaryValue) {
                // Set primary value automatically for all next mappers
                $this->entity->{$primaryProperty->getName()} = $primaryValue = $insertedPrimaryValue;
            }
        }

        return $this->entity;
    }

}