<?php

namespace UniMapper\Query;

use UniMapper\Entity\Reflection\Property\Option\Map;
use UniMapper\Exception;

trait Sortable
{

    protected $orderBy = [];

    public function orderBy($name, $direction = Select::ASC)
    {
        if (!$this->reflection->hasProperty($name)) {
            throw new Exception\QueryException(
                "Invalid property name '" . $name . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== Select::ASC && $direction !== Select::DESC) {
            throw new Exception\QueryException("Order direction must be '" . Select::ASC . "' or '" . Select::DESC . "'!");
        }

        $property = $this->reflection->getProperty($name);
        if ($property->hasOption(Map::KEY) && !$property->getOption(Map::KEY)) {
            throw new Exception\QueryException(
                "Order can not be used on properties with disabled mapping!"
            );
        }

        $this->orderBy[$this->reflection->getProperty($name)->getUnmapped()] = $direction;
        return $this;
    }

}
