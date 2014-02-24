<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Reflection\EntityReflection,
    UniMapper\EntityCollection,
    UniMapper\Query\IConditionable,
    UniMapper\Query\Object\Condition,
    UniMapper\Query\Object\Order;

/**
 * ORM query object
 */
class FindAll extends \UniMapper\Query implements IConditionable
{

    public $limit = 0;
    public $offset = 0;
    public $orders = array(); // @todo split orders by mappers
    public $selection = array();

    public function __construct(EntityReflection $entityReflection, array $mappers, array $selection = array())
    {
        parent::__construct($entityReflection, $mappers);
        $this->selection = $selection;
    }

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

    public function orderBy($propertyName, $direction = "asc")
    {
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException("Invalid property name '" . $propertyName . "'!");
        }

        $order = new Order($propertyName);
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

    public function onExecute()
    {
        $result = false;

        // Add properties from conditions to the selection if not set
        if (count($this->conditions) > 0 && count($this->selection) > 0) {
            foreach ($this->conditions as $condition) {
                $propertyName = $condition->getExpression();
                if (!isset($this->selection[$propertyName])) {
                    $this->selection[] = $propertyName;
                }
            }
        }

        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            $mapper = $this->mappers[$mapperName];

            if ($result instanceof EntityCollection && $this->entityReflection->getPrimaryProperty()) {
                $this->conditions["hybrid"] = new Condition($this->entityReflection->getPrimaryProperty()->getName(), "IN", $result->getKeys());
            }
            $data = $mapper->findAll($this);
            if ($data === false) {
                continue;
            }
            if (isset($this->conditions["hybrid"])) {
                unset($this->conditions["hybrid"]);
            }

            if ($result instanceof EntityCollection && $data instanceof EntityCollection) {
                // There are some results from previous queries, so merge it
                $result->merge($data);
            } else {
                $result = $data;
            }
        }

        return $result;
    }

}