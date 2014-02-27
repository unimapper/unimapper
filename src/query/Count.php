<?php

namespace UniMapper\Query;

/**
 * ORM query object
 */
class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute()
    {
        if ($this->hasHybridCondition()) {
            throw new \Exception("Count for hybrid entities not yet implemented!");
        } else {
            $mapper = array_shift($this->mappers);
            return $mapper->count($this);
        }
    }

}