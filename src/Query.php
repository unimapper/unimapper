<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Query\IQuery,
    UniMapper\EntityCollection,
    UniMapper\Exceptions\QueryException;

abstract class Query implements IQuery
{

    /** @var integer */
    private $elapsed;

    /** @var mixed */
    private $result;

    /** @var array */
    protected $conditionOperators = ["=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE", "COMPARE", "IN"];

    /** @var array */
    protected $conditions = [];

    /** @var array */
    protected $mappers;

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    public function __construct(Reflection\Entity $entityReflection, array $mappers)
    {
        if (!isset($mappers[$entityReflection->getMapperReflection()->getName()])) {
            throw new QueryException(
                "Mapper '" . $entityReflection->getMapperReflection()->getName() . "' not given!"
            );
        }

        $this->mappers = $mappers;
        $this->entityReflection = $entityReflection;
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

    protected function addCondition($propertyName, $operator, $value, $joiner = 'AND')
    {
        if (!$this instanceof Query\IConditionable) {
            throw new QueryException("Conditions can be added only on conditionable queries!");
        }

        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }

        if ($operator !== null && !in_array($operator, $this->conditionOperators)) {
            throw new QueryException("Condition operator " . $operator . " not allowed! You can use one of the following " . implode(" ", $this->conditionOperators) . ".");
        }

        $propertyReflection = $this->entityReflection->getProperty($propertyName);
        if ($propertyReflection->isAssociation() || $propertyReflection->isComputed()) {
            throw new QueryException("Condition can not be called on associations and computed properties!");
        }

        $this->conditions[] = [
            $propertyReflection->getMappedName(),
            $operator,
            $value,
            $joiner
        ];
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = new $this($this->entityReflection, $this->mappers);

        call_user_func($callback, $query);

        if (count($query->conditions) === 0) {
            throw new QueryException("Nested query must contain one condition at least!");
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

        $currentMapperName = $this->entityReflection->getMapperReflection()->getName();
        if (!isset($this->mappers[$currentMapperName])) {
            throw new QueryException("Mapper with name '" . $currentMapperName . "' not given!");
        }
        $this->result = $this->onExecute($this->mappers[$currentMapperName]);
        $this->elapsed = microtime(true) - $start;

        return $this->result;
    }

}