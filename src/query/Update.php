<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Reflection;

class Update extends \UniMapper\Query implements IConditionable
{

    /** @var \UniMapper\Entity */
    public $entity;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, array $data)
    {
        parent::__construct($entityReflection, $mappers);
        $class = $entityReflection->getClassName();
        $this->entity = new $class; // @todo missing cache
        $this->entity->import($data); // @todo better validation, prevent from primary property change?
    }

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        $this->beforeExecute();
        return $mapper->update($this);
    }

    public function executeHybrid()
    {
        $this->beforeExecute();

        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        // Try to get appropriate records first
        $query = new FindAll($this->entityReflection, $this->mappers, $primaryProperty->getName());
        $query->conditions = $this->conditions;
        $entities = $query->execute();

        if ($entities === false) {
            return false;
        }

        $status = false;
        $this->conditions = array(
            array($primaryProperty->getName(), "IN", $this->getPrimaryValuesFromCollection($entities), "AND")
        );
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {
            if ($this->mappers[$mapperName]->update($this)) {
                $status = true;
            }
        }
        return true;
    }

    private function beforeExecute()
    {
        if (count($this->conditions) === 0) {
            throw new QueryException("At least one condition must be set!");
        }

        // @todo will be removed when primary property must be required
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new QueryException("Entity does not have primary property!");
        }

        // Ignore primary property value
        if (isset($this->entity->{$primaryProperty->getName()})) {
            unset($this->entity->{$primaryProperty->getName()});
        }
    }

}