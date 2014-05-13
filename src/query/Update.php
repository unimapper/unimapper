<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Mapper,
    UniMapper\Reflection;

class Update extends \UniMapper\Query implements IConditionable
{

    /** @var \UniMapper\Entity */
    public $entity;

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, array $data)
    {
        parent::__construct($entityReflection, $mapper);
        $class = $entityReflection->getClassName();
        $this->entity = new $class; // @todo missing cache
        $this->entity->import($data); // @todo better validation, prevent from primary property change?
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $this->beforeExecute();
        return $mapper->update($this);
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