<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        return (int) $adapter->count(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $this->conditions
        );
    }

}