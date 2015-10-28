<?php

namespace UniMapper\Query;

use UniMapper\Exception;

trait Sortable
{

    protected $orderBy = [];

    public function orderBy($name, $direction = Select::ASC)
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\QueryException(
                "Invalid property name '" . $name . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== Select::ASC && $direction !== Select::DESC) {
            throw new Exception\QueryException("Order direction must be '" . Select::ASC . "' or '" . Select::DESC . "'!");
        }

        $this->orderBy[$this->entityReflection->getProperty($name)->getName(true)] = $direction;
        return $this;
    }

}
