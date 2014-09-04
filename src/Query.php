<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Query\IQuery,
    UniMapper\Exception\QueryException;

abstract class Query implements IQuery
{

    /** @var integer */
    private $elapsed;

    /** @var mixed */
    private $result;

    /** @var array */
    protected $conditionOperators = [
        "=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE",
        "COMPARE", "IN"
    ];

    /** @var array */
    protected $conditions = [];

    /** @var array */
    protected $adapters = [];

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    public function __construct(Reflection\Entity $reflection, array $adapters)
    {
        if (!$reflection->hasAdapter()) {
            throw new QueryException(
                "Entity '" . $reflection->getClassName()
                . "' has no adapter defined!"
            );
        }

        if (!isset($adapters[$reflection->getAdapterReflection()->getName()])) {
            throw new QueryException(
                "Adapter '" . $reflection->getAdapterReflection()->getName()
                . "' not given!"
            );
        }

        $this->adapters = $adapters;
        $this->entityReflection = $reflection;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function getElapsed()
    {
        return $this->elapsed;
    }

    public function getEntityReflection()
    {
        return $this->entityReflection;
    }

    public static function getName()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return lcfirst($reflection->getShortName());
    }

    protected function addCondition($name, $operator, $value, $joiner = 'AND')
    {
        if (!$this instanceof Query\IConditionable) {
            throw new QueryException(
                "Conditions can be added only on conditionable queries!"
            );
        }

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

        $propertyReflection = $this->entityReflection->getProperty($name);
        if ($propertyReflection->isAssociation()
            || $propertyReflection->isComputed()
        ) {
            throw new QueryException(
                "Condition can not be called on associations and computed "
                . "properties!"
            );
        }

        $this->conditions[] = [$name, $operator, $value, $joiner];
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

    final public function execute()
    {
        $start = microtime(true);

        $adapterName = $this->entityReflection->getAdapterReflection()->getName();
        if (!isset($this->adapters[$adapterName])) {
            throw new QueryException(
                "Adapter with name '" . $adapterName . "' not given!"
            );
        }
        $this->result = $this->onExecute($this->adapters[$adapterName]);
        $this->elapsed = microtime(true) - $start;

        return $this->result;
    }

    /**
     * Group associative array
     *
     * @param array $original
     * @param array $keys
     * @param int   $level
     *
     * @return array
     *
     * @link http://tigrou.nl/2012/11/26/group-a-php-array-to-a-tree-structure/
     *
     * @throws \Exception
     */
    protected function groupResult($original, $keys, $level = 0)
    {
        $converted = [];
        $key = $keys[$level];
        $isDeepest = sizeof($keys) - 1 == $level;

        $level++;

        $filtered = [];
        foreach ($original as $k => $subArray) {

            $subArray = (array) $subArray;
            if (!isset($subArray[$key])) {
                throw new \Exception(
                    "Index '" . $key . "' not found on level '" . $level . "'!"
                );
            }

            $thisLevel = $subArray[$key];
            if ($isDeepest) {
                $converted[$thisLevel] = $subArray;
            } else {
                $converted[$thisLevel] = [];
            }
            $filtered[$thisLevel][] = $subArray;
        }

        if (!$isDeepest) {
            foreach (array_keys($converted) as $value) {
                $converted[$value] = $this->groupResult(
                    $filtered[$value],
                    $keys,
                    $level
                );
            }
        }

        return $converted;
    }

}