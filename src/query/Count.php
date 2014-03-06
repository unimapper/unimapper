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
            foreach ($this->entityReflection->getMappers() as $name => $mapper) {
                return $this->mappers[$name]->count($this);
            }
        }
    }

}