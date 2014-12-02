<?php

namespace UniMapper\Query;

use UniMapper\Exception;

abstract class Conditionable extends \UniMapper\Query
{

    /** @var array */
    protected $operators = [
        "=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE",
        "COMPARE", "IN", "NOT IN"
    ];

    /** @var array */
    protected $conditions = [];

    protected function addCondition($name, $operator, $value, $joiner = 'AND')
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\QueryException("Invalid property name '" . $name . "'!");
        }

        if ($operator !== null && !in_array($operator, $this->operators)) {
            throw new Exception\QueryException(
                "Condition operator " . $operator . " not allowed! "
                . "You can use one of the following "
                . implode(" ", $this->operators) . "."
            );
        }

        $property = $this->entityReflection->getProperty($name);
        if ($property->isAssociation()
            || $property->isComputed()
            || $property->isTypeCollection()
            || $property->isTypeEntity()
        ) {
            throw new Exception\QueryException(
                "Conditions are not allowed on associations, computed, collections or entities!"
            );
        }

        if (($operator === "IN" || $operator === "NOT IN") && !is_array($value)) {
            throw new Exception\QueryException("Value must be type array when using operator IN or NOT IN!");
        }

        // Validate value type
        try {

            if ((is_array($value) && $property->getType() !== "array")
                && ($operator === "IN" || $operator === "NOT IN")
            ) {

                foreach ($value as $index => $item) {

                    $property->validateValueType($item);
                    $value[$index] = $this->mapper->unmapValue($property, $item);
                }
            } elseif (!in_array($operator, ["IS", "IS NOT"]) && $value !== null) {

                $property->validateValueType($value);
                $value = $this->mapper->unmapValue($property, $value);
            }
        } catch (Exception\PropertyValueException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->conditions[] = [
            $property->getName(true),
            $operator,
            $value,
            $joiner
        ];
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection, $this->adapters, $this->mapper);

        call_user_func($callback, $query);

        if (count($query->conditions) === 0) {
            throw new Exception\QueryException(
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