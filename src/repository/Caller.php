<?php

namespace UniMapper\Repository;

class Caller
{

    /** @var \UniMapper\QueryBuilder */
    private $queryBuilder;

    /** @var string */
    private $entityName;

    public function __construct(\UniMapper\QueryBuilder $queryBuilder, $entityName)
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityName = $entityName;
    }

    public function __call($name, $arguments)
    {
        array_unshift($arguments, $this->entityName);
        return call_user_func_array([$this->queryBuilder, $name], $arguments);
    }

}
