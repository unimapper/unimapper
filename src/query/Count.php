<?php

namespace UniMapper\Query;

/**
 * ORM query object
 */
class Count extends \UniMapper\Query implements IConditionable
{

    public function onExecute()
    {
        $hasHybridCondition = false;
        if ($this->entityReflection->isHybrid()) {
            foreach ($this->conditions as $condition) {
                $property = $this->entityReflection->getProperty($condition->getExpression());
                if ($property->getMapping()->isHybrid()) {
                    $hasHybridCondition = true;
                    break;
                }
            }
        }

        if ($hasHybridCondition) {
            throw new \Exception("Count for hybrid entities not yet implemented!");
        } else {
            $mapper = array_shift($this->mappers);
            return $mapper->count($this);
        }
    }

}