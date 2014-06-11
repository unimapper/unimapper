<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Mapper,
    UniMapper\Reflection,
    UniMapper\EntityCollection,
    UniMapper\Query\IConditionable;

class FindAll extends \UniMapper\Query implements IConditionable
{

    public $limit = null;
    public $offset = null;
    public $orderBy = [];
    public $selection = [];

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper)
    {
        parent::__construct($entityReflection, $mapper);
        $this->selection = array_slice(func_get_args(), 2);
    }

    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    public function orderBy($propertyName, $direction = "asc")
    {
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }

        $direction = strtolower($direction);
        if ($direction !== "asc" && $direction !== "desc") {
            throw new QueryException("Order direction must be 'asc' or 'desc'!");
        }
        $this->orderBy[$propertyName] = $direction;
        return $this;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $result = $mapper->findAll(
            $this->entityReflection->getMapperReflection()->getResource(),
            $this->getSelection($this->selection),
            $this->conditions,
            $this->getOrderBy($this->orderBy),
            $this->limit,
            $this->offset
        );
        if ($result === false) {
            return new EntityCollection($this->entityReflection->getClassName());
        }

        return $mapper->mapCollection($this->entityReflection->getClassName(), $result);
    }

    protected function addCondition($propertyName, $operator, $value, $joiner = 'AND')
    {
        parent::addCondition($propertyName, $operator, $value, $joiner);

        // Add properties from conditions
        if (count($this->selection) > 0 && !in_array($propertyName, $this->selection)) {
            $this->selection[] = $propertyName;
        }
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = parent::addNestedConditions($callback, $joiner);
        // Add properties from conditions
        $this->selection = array_unique(array_merge($this->selection, $query->selection));
    }

    private function getSelection(array $selection)
    {
        if (count($selection) === 0) {
            // Select all if not set

            $selection = array_keys($this->entityReflection->getProperties());
        } else {
            // Add primary property automatically if not set in selection

            $primaryPropertyName = $this->entityReflection->getPrimaryProperty()->getName();
            if (!in_array($primaryPropertyName, $selection)) {
                $selection[] = $primaryPropertyName;
            }
        }

        $result = [];
        foreach ($selection as $name) {

            if ($this->entityReflection->hasProperty($name)) {
                $result[] = $this->entityReflection->getProperty($name)->getMappedName();
            }
        }
        return $result;
    }

    private function getOrderBy(array $items)
    {
        $unmapped = [];
        foreach ($items as $name => $direction) {
            $mappedName = $this->entityReflection->getProperties()[$name]->getMappedName();
            $unmapped[$mappedName] = $direction;
        }
        return $unmapped;
    }

}