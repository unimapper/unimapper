<?php

namespace UniMapper\Query;

use UniMapper\Query\FindAll;

/**
 * Delete query
 */
class Delete extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    protected function onExecute()
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        if ($this->entityReflection->isHybrid()) {
            $this->deleteHybrid();
        } else {
            foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {
                return $this->mappers[$mapperName]->delete($this);
            }
        }
    }

    private function deleteHybrid()
    {
        // @todo primary property must be required
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Entity does not have primary property!");
        }

        // Try to get appropriate records first
        $query = new FindAll($this->entityReflection, $this->mappers);
        $query->conditions = $this->conditions;
        $entities = $query->execute();

        if (!$entities) {
            return false;
        }

        $this->conditions = array(array($primaryProperty->getName(), "IN", $entities->getKeys(), "AND"));
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {
            $this->mappers[$mapperName]->delete($this);
        }

        return true;
    }

}