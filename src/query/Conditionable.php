<?php

namespace UniMapper\Query;

use UniMapper\Exception;

abstract class Conditionable extends \UniMapper\Query
{

    /** @var array */
    private $operators = [
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
                "Conditions are not allwed on associations, computed, collections or entities!"
            );
        }

        // Validate value type
        try {

            if (is_array($value) && $property->getType() !== "array") {

                foreach ($value as $item) {
                    $property->validateValueType($item);
                }
            } else {
                $property->validateValueType($value);
            }
        } catch (Exception\PropertyValueException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->conditions[] = [
            $property->getName(true),
            $operator,
            $this->adapters[$this->entityReflection->getAdapterReflection()->getName()]
                ->getMapping()
                ->unmapValue($property, $value),
            $joiner
        ];
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection, $this->adapters);

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