<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\EntityCollection,
    UniMapper\Exceptions\QueryException;

/**
 * ORM query object
 */
abstract class Query
{

    public $conditions = array();
    protected $conditionOperators = array("=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE", "COMPARE", "IN");
    public $mappers = array();
    public $elapsed;
    public $result = null;

    /** @var \UniMapper\Reflection\Entity */
    public $entityReflection;

    public function __construct(Reflection\Entity $entityReflection, array $mappers)
    {
        if (count($mappers) === 0) {
            throw new QueryException("Query can not be used without mappers!");
        }
        $this->mappers = $mappers;

        if (count($entityReflection->getMappers()) === 0) {
            throw new QueryException("Missing mapper definition in entity " . $entityReflection->getName() . "!");
        }
        $this->entityReflection = $entityReflection;
    }

    public static function getName()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return lcfirst($reflection->getShortName());
    }

    protected function addCondition($propertyName, $operator, $value, $joiner = 'AND')
    {
        if (!$this instanceof Query\IConditionable) {
            throw new QueryException("Conditions should be called only on conditionable queries!");
        }

        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }

        if ($operator !== null && !in_array($operator, $this->conditionOperators)) {
            throw new QueryException("Condition operator " . $operator . " not allowed! You can use one of the following " . implode(" ", $this->conditionOperators) . ".");
        }

        $this->conditions[] = array($propertyName, $operator, $value, $joiner);
        return $this;
    }

    public function where($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value);
        return $this;
    }

    public function orWhere($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value, "OR");
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

                list($propertyName) = $condition;

                $property = $this->entityReflection->getProperty($propertyName);
                if ($property->getMapping()->isHybrid()) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getPrimaryValuesFromCollection(EntityCollection $collection)
    {
        $keys = array();

        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty === null) {
            throw new \Exception("Primary property not set in entity " . $this->entityReflection->getName() . "!"); // @todo remove when primary property is required
        }

        foreach ($collection as $entity) {

            if (isset($entity->{$primaryProperty->getName()})) {
                $keys[] = $entity->{$primaryProperty->getName()};
            }
        }
        return $keys;
    }
}