<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        return $mapper->count($this);
    }

}