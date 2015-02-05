<?php

namespace UniMapper\Query;

use UniMapper\Exception;
use UniMapper\Reflection;

trait Conditionable
{

    /** @var array */
    protected $operators = [
        "=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE", "IN",
        "NOT IN"
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
        if ($property->hasOption(Reflection\Property::OPTION_ASSOC)
            || $property->hasOption(Reflection\Property::OPTION_COMPUTED)
            || $property->getType() === Reflection\Property::TYPE_COLLECTION
            || $property->getType() === Reflection\Property::TYPE_ENTITY
        ) {
            throw new Exception\QueryException(
                "Conditions are not allowed on associations, computed, collections or entities!"
            );
        }

        if (($operator === "IN" || $operator === "NOT IN") && !is_array($value)) {
            throw new Exception\QueryException(
                "Value must be type array when using operator IN or NOT IN!"
            );
        }

        if ($operator !== "IS" && $operator !== "IS NOT" && $value === null) {
            throw new Exception\QueryException(
                "Null value can be combined only with IS and IS NOT!"
            );
        }

        // Validate value type
        try {

            if ((is_array($value) && $property->getTypeOption() !== "array")
                && ($operator === "IN" || $operator === "NOT IN")
            ) {

                foreach ($value as $index => $item) {

                    $property->validateValueType($item);
                    $value[$index] = $item;
                }
            } elseif (!in_array($operator, ["IS", "IS NOT"]) && $value !== null) {

                $property->validateValueType($value);
            }
        } catch (Exception\InvalidArgumentException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->conditions[] = [
            $property->getName(),
            $operator,
            $value,
            $joiner
        ];
    }

    protected function addConditionGroup(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection);

        call_user_func($callback, $query);

        if (count($query->conditions) === 0) {
            throw new Exception\QueryException(
                "Condition group must contain one condition at least!"
            );
        }

        $this->conditions[] = [$query->conditions, $joiner];

        return $query;
    }

    public function where($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value);
        return $this;
    }

    public function whereAre(\Closure $callback)
    {
        $this->addConditionGroup($callback);
        return $this;
    }

    public function orWhereAre(\Closure $callback)
    {
        $this->addConditionGroup($callback, "OR");
        return $this;
    }

    public function orWhere($propertyName, $operator, $value)
    {
        $this->addCondition($propertyName, $operator, $value, "OR");
        return $this;
    }

    protected function unmapConditions(\UniMapper\Mapper $mapper, array $conditions)
    {
        foreach ($conditions as $index => $condition) {

            if (is_array($condition[0])) {
                // Group

                $conditions[$index][0] = $this->unmapConditions($mapper, $condition[0]);
            } else {
                // Condition

                $property = $this->entityReflection->getProperty($condition[0]);

                // Unmap value
                $conditions[$index][2] = $mapper->unmapValue(
                    $property,
                    $condition[2]
                );

                // Unmap name
                $conditions[$index][0] = $property->getName(true);
            }
        }

        return $conditions;
    }

}
