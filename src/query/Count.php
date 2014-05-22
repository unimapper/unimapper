<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        return (integer) $mapper->count(
            $mapper->getResource($this->entityReflection),
            $mapper->unmapConditions($this->entityReflection, $this->conditions)
        );
    }

}