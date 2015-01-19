<?php

namespace UniMapper\Query;

use UniMapper\Exception;

trait Conditionable
{

    /** @var array */
    protected $conditions = [];

    public function __call($name, $args)
    {
        $condition = new \UniMapper\Condition($this->entityReflection);

        try {
            call_user_func_array([$condition, $name], $args);
        } catch (Exception\ConditionException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->conditions = array_merge($this->conditions, $condition->getConditions());

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
