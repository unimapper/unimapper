<?php

namespace UniMapper\Query;

class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        return (int) $mapper->count(
            $this->entityReflection->getMapperReflection()->getResource(),
            $this->conditions
        );
    }

}