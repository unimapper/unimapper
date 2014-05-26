<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Mapper,
    UniMapper\Reflection;

class Update extends \UniMapper\Query implements IConditionable
{

    /** @var array */
    private $values = [];

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, array $data)
    {
        parent::__construct($entityReflection, $mapper);

        // Primary value update is not allowed
        $primaryName = $entityReflection->getPrimaryProperty()->getMappedName();
        if (isset($data[$primaryName])) {
            throw new QueryException("Update is not allowed on primary property '" .  $primaryName . "'!");
        }

        $class = $entityReflection->getClassName();
        $entity = new $class;
        $entity->import($data); // @todo easier validation

        $this->values = $mapper->unmapEntity($entity);

        // Values can not be empty
        if (empty($this->values)) {
            throw new QueryException("Nothing to insert");
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        $mapper->update(
            $mapper->getResource($this->entityReflection),
            $this->values,
            $mapper->unmapConditions($this->entityReflection, $this->conditions)
        );
    }

}