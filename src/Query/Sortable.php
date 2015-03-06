<?php

namespace UniMapper\Query;

use UniMapper\Exception;

trait Sortable
{

    protected $orderBy = [];

    public function orderBy($name, $direction = self::ASC)
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\QueryException(
                "Invalid property name '" . $name . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== self::ASC && $direction !== self::DESC) {
            throw new Exception\QueryException("Order direction must be 'asc' or 'desc'!");
        }

        $this->orderBy[$this->entityReflection->getProperty($name)->getName(true)] = $direction;
        return $this;
    }

}
