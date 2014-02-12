<?php

namespace UniMapper;

use UniMapper\Query\Object\Condition,
    UniMapper\Exceptions\QueryException;

/**
 * ORM query object
 */
abstract class Query
{

    public $conditions = array();
    public $mappers = array();

    /** @var \UniMapper\Reflection\EntityReflection */
    public $entityReflection;

    public function __construct(\UniMapper\Entity $entity, array $mappers)
    {
        $this->entityReflection = $entity->getReflection();
        $this->mappers = $mappers;
    }

    public function where($propertyName, $operator, $value)
    {
        if (!$this instanceof Query\IConditionable) {
            throw new QueryException("Conditions should be called only on conditionable queries!");
        }
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }
        $this->conditions[] = new Condition($propertyName, $operator, $value);
        return $this;
    }

}