<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function executeSimple()
    {
        return array_values($this->mappers)[0]->count($this);
    }

    public function executeHybrid()
    {
        if ($this->hasHybridCondition()) {
            throw new \Exception("Count for hybrid entities not yet implemented!");
        }

        return $this->executeSimple(); // @todo
    }

}