<?php

namespace UniMapper\Query;

class Count extends Conditionable
{

    protected function onExecute(\UniMapper\Adapter\IAdapter $adapter)
    {
        $query = $adapter->createCount(
            $this->entityReflection->getAdapterReflection()->getResource()
        );
        $query->setConditions($this->conditions);
        $result = (int) $adapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

        return $result;
    }

}