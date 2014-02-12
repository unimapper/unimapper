<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\EntityCollection,
    UniMapper\Query\IConditionable,
    UniMapper\Query\ICountable,
    UniMapper\Query\Object\Condition,
    UniMapper\Query\Object\Order;

/**
 * ORM query object
 */
class FindAll extends \UniMapper\Query implements IConditionable, ICountable
{

    public $limit = 0;
    public $offset = 0;
    public $orders = array(); // @todo split orders by mappers
    public $count = false;
    public $selection = array();

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function order($propertyName, $direction = "asc")
    {
        $order = new Order($propertyName, $direction);
        $direction = strtolower($direction);
        if ($direction === "asc") {
            $order->asc = true;
        } elseif ($direction === "desc") {
            $order->desc = true;
        } else {
            throw new QueryException("Order direction must be 'asc' or 'desc'!");
        }
        $this->orders[] = $order;
        return $this;
    }

    public function count()
    {
        $this->count = true;
        return $this;
    }

    public function execute()
    {
        if ($this->count) {
            return $this->executeCount();
        }

        $result = false;
        $entityMappers = $this->entityReflection->getMappers();

        foreach ($this->mappers as $mapper) {

            if (isset($entityMappers[get_class($mapper)])) {

                if ($result instanceof EntityCollection) {
                    $this->conditions["generated"] = new Condition($this->entityReflection->getPrimaryProperty()->getName(), "IN", $result->getKeys());
                }
                $data = $mapper->findAll($this);
                if (isset($this->conditions["generated"])) {
                    unset($this->conditions["generated"]);
                }

                if ($result instanceof EntityCollection && $data instanceof EntityCollection) {
                    // There are some results from previous queries, so merge it
                    $result->merge($data);
                } else {
                    $result = $data;
                }
            }
        }

        return $result;
    }

    protected function executeCount()
    {
        $hasHybridCondition = false;
        if ($this->entityReflection->isHybrid()) {
            foreach ($this->conditions as $condition) {
                $property = $this->entityReflection->getProperty($condition->getExpression());
                if ($property->getMapping()->isHybrid()) {
                    $hasHybridCondition = true;
                    break;
                }
            }
        }

        if ($hasHybridCondition) {
            throw new Exception("Count for hybrid entities not yet implemented!");
        } else {
            $mapper = array_shift($this->mappers);
            return $mapper->count($this);
        }
    }

}