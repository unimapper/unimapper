<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        $mapping = $adapter->getMapping();
        return (int) $adapter->count(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $mapping->unmapConditions($this->entityReflection, $this->conditions)
        );
    }

}