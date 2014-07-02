<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Mapper,
    UniMapper\Reflection;

class UpdateOne extends \UniMapper\Query implements IConditionable
{

    /** @var \UniMapper\Entity */
    private $entity;

    private $primaryValue;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, $primaryValue, array $data)
    {
        parent::__construct($entityReflection, $mappers);

        $this->primaryValue = $primaryValue;

        // Primary value update is not allowed
        if (!$entityReflection->hasPrimaryProperty()) {
            throw new QueryException("Entity '" . $entityReflection->getClassName() . "' has no primary property!");
        }

        // Do not change primary value
        unset($data[$entityReflection->getPrimaryProperty()->getName()]);

        $this->entity = $entityReflection->createEntity();
        $this->entity->import($data); // @todo easier validation
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getPrimaryValue()
    {
        return $this->primaryValue;
    }

    public function onExecute(Mapper $mapper)
    {
        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new QueryException("Nothing to update!");
        }

        $mapper->updateOne(
            $this->entityReflection->getMapperReflection()->getResource(),
            $this->entityReflection->getPrimaryProperty()->getMappedName(),
            $this->primaryValue,
            $values
        );
    }

}
