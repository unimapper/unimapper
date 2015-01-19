<?php

namespace UniMapper;

class Condition
{

    /** @var array */
    protected $operators = [
        "=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE", "IN",
        "NOT IN"
    ];

    /** @var array */
    protected $conditions = [];

    private $entityReflection;

    public function __construct(Reflection\Entity $reflection)
    {
        $this->entityReflection = $reflection;
    }

    protected function add($name, $operator, $value, $joiner = 'AND')
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\ConditionException("Invalid property name '" . $name . "'!");
        }

        if ($operator !== null && !in_array($operator, $this->operators)) {
            throw new Exception\ConditionException(
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
            throw new Exception\ConditionException(
                "Conditions are not allowed on associations, computed, collections or entities!"
            );
        }

        if (($operator === "IN" || $operator === "NOT IN") && !is_array($value)) {
            throw new Exception\ConditionException(
                "Value must be type array when using operator IN or NOT IN!"
            );
        }

        if ($operator !== "IS" && $operator !== "IS NOT" && $value === null) {
            throw new Exception\ConditionException(
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
            throw new Exception\ConditionException($e->getMessage());
        }

        $this->conditions[] = [
            $property->getName(),
            $operator,
            $value,
            $joiner
        ];
    }

    protected function addGroup(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection);

        call_user_func($callback, $query);

        if (count($query->conditions) === 0) {
            throw new Exception\ConditionException(
                "Condition group must contain one condition at least!"
            );
        }

        $this->conditions[] = [$query->conditions, $joiner];

        return $query;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function where($propertyName, $operator, $value)
    {
        $this->add($propertyName, $operator, $value);
        return $this;
    }

    public function whereAre(\Closure $callback)
    {
        $this->addGroup($callback);
        return $this;
    }

    public function orWhereAre(\Closure $callback)
    {
        $this->addGroup($callback, "OR");
        return $this;
    }

    public function orWhere($propertyName, $operator, $value)
    {
        $this->add($propertyName, $operator, $value, "OR");
        return $this;
    }

}