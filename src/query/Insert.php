<?php

namespace UniMapper\Query;

use UniMapper\Mapper,
    UniMapper\Exceptions\QueryException,
    UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    public $entity;

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, array $data)
    {
        parent::__construct($entityReflection, $mapper);
        $class = $this->entityReflection->getClassName();
        $this->entity = new $class; // @todo missing cache
        $this->entity->import($data); // @todo better validation?
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $primaryValue = $mapper->insert($this);
        if ($primaryValue === null) {
            throw new QueryException("Insert should return primary value but null given!");
        }
        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        $this->entity->{$primaryProperty->getName()} = $primaryValue;
        return $this->entity;
    }

}