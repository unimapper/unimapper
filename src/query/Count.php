<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        return $mapper->count($this);
    }

    public function executeHybrid()
    {
        if ($this->hasHybridCondition()) {
            throw new \Exception("Count for hybrid entities not yet implemented!");
        }

        // @todo
        foreach ($this->entityReflection->getMappers() as $name => $mapperReflection) {
            return $this->executeSimple($this->mappers[$name]);
        }
    }

}