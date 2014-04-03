<?php

namespace UniMapper\Query;

use UniMapper\Query\FindAll,
    UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable;

class Delete extends \UniMapper\Query implements IConditionable
{

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        $this->beforeExecute();
        return $mapper->delete($this);
    }

    public function executeHybrid()
    {
        $this->beforeExecute();

        // @todo primary property must be required
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Entity does not have primary property!");
        }

        // Try to get appropriate records first
        $query = new FindAll($this->entityReflection, $this->mappers, $primaryProperty->getName());
        $query->conditions = $this->conditions;
        $entities = $query->execute();

        if (!$entities) {
            return false;
        }

        $this->conditions = array(array($primaryProperty->getName(), "IN", $this->getPrimaryValuesFromCollection($entities), "AND"));
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {
            $this->mappers[$mapperName]->delete($this);
        }

        return true;
    }

    private function beforeExecute()
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }
    }

}