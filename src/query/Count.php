<?php

namespace UniMapper\Query;

class Count extends Conditionable
{

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $query = $adapter->createCount(
            $this->entityReflection->getAdapterReflection()->getResource()
        );
        $query->setConditions($this->conditions);
        return (int) $adapter->execute($query);
    }

}