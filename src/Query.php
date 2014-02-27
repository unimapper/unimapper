<?php

namespace UniMapper;

use UniMapper\Reflection\EntityReflection,
    UniMapper\Query\Object\Condition,
    UniMapper\Exceptions\QueryException;

/**
 * ORM query object
 */
abstract class Query
{

    public $conditions = array();
    public $mappers = array();
    public $elapsed;
    public $finished = false;
    public $result = null;

    /** @var \UniMapper\Reflection\EntityReflection */
    public $entityReflection;

    public function __construct(EntityReflection $entityReflection, array $mappers)
    {
        if (count($mappers) === 0) {
            throw new QueryException("Query can not be used without mappers!");
        }
        $this->mappers = $mappers;

        $this->entityReflection = $entityReflection;
        if (count($this->entityReflection->getMappers()) === 0) {
            throw new QueryException("Missing mapper definition in entity " . $this->entityReflection->getName() . "!");
        }
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

    final public function execute()
    {
        $start = microtime(true);
        $this->result = $this->onExecute();
        $this->elapsed = microtime(true) - $start;
        return $this->result;
    }

    protected function hasHybridCondition()
    {
        if ($this->entityReflection->isHybrid()) {
            foreach ($this->conditions as $condition) {
                $property = $this->entityReflection->getProperty($condition->getExpression());
                if ($property->getMapping()->isHybrid()) {
                    return true;
                }
            }
        }
        return false;
    }

}