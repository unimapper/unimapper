<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Entity,
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

    public function __construct(Entity $entity, array $mappers, array $selection = array())
    {
        parent::__construct($entity, $mappers);
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

    public function execute()
    {
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

}