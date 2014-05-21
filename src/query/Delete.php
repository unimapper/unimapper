<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable;

class Delete extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        $mapper->delete(
            $mapper->getResource($this->entityReflection),
            $mapper->unmapConditions($this->entityReflection, $this->conditions)
        );
    }

}