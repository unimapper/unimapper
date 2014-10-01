<?php

namespace UniMapper\Query;

class Count extends Conditionable
{

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        return (int) $adapter->count(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $adapter->getMapping()->unmapConditions(
                $this->conditions,
                $this->entityReflection
            )
        );
    }

}