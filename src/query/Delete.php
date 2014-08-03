<?php

namespace UniMapper\Query;

use UniMapper\Exception\QueryException,
    UniMapper\Query\IConditionable;

class Delete extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        $adapter->delete(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $this->conditions
        );
    }

}