<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Reflection;

class Update extends \UniMapper\Query implements IConditionable
{

    /** @var \UniMapper\Entity */
    private $entity;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, array $data)
    {
        parent::__construct($entityReflection, $mappers);

        // Primary value update is not allowed
        if ($entityReflection->hasPrimaryProperty()) {

            $primaryName = $entityReflection->getPrimaryProperty()->getMappedName();
            if (isset($data[$primaryName])) {
                throw new QueryException("Update is not allowed on primary property '" .  $primaryName . "'!");
            }
        }

        $class = $entityReflection->getClassName();
        $this->entity = new $class;
        $this->entity->import($data); // @todo easier validation
    }

    public function getValues()
    {
        return $this->entity->getData();
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new QueryException("Nothing to update!");
        }

        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        $mapper->update(
            $this->entityReflection->getMapperReflection()->getResource(),
            $values,
            $this->conditions
        );
    }

}