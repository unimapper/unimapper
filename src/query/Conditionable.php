<?php

namespace UniMapper\Query;

use UniMapper\Exception\QueryException;

abstract class Conditionable extends \UniMapper\Query
{

    /** @var array */
    protected $conditionOperators = [
        "=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE",
        "COMPARE", "IN"
    ];

    /** @var array */
    protected $conditions = [];

    protected function addCondition($name, $operator, $value, $joiner = 'AND')
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new QueryException("Invalid property name '" . $name . "'!");
        }

        if ($operator !== null && !in_array($operator, $this->conditionOperators)) {
            throw new QueryException(
                "Condition operator " . $operator . " not allowed! "
                . "You can use one of the following "
                . implode(" ", $this->conditionOperators) . "."
            );
        }

        $property = $this->entityReflection->getProperty($name);
        if ($property->isAssociation()
            || $property->isComputed()
        ) {
            throw new QueryException(
                "Condition can not be called on associations and computed "
                . "properties!"
            );
        }

        $this->conditions[] = [$property->getName(true), $operator, $value, $joiner];
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection, $this->adapters);

        call_user_func($callback, $query);

        if (count($query->conditions) === 0) {
            throw new QueryException(
                "Nested query must contain one condition at least!"
            );
        }

        $this->conditions[] = array($query->conditions, $joiner);

        return $query;
    }

    public function where($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value);
        return $this;
    }

    public function whereAre(\Closure $callback)
    {
        $this->addNestedConditions($callback);
        return $this;
    }

    public function orWhereAre(\Closure $callback)
    {
        $this->addNestedConditions($callback, "OR");
        return $this;
    }

    public function orWhere($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value, "OR");
        return $this;
    }

}