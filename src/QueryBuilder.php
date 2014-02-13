<?php

namespace UniMapper;

use UniMapper\Query\FindOne,
    UniMapper\Query\Count,
    UniMapper\Query\FindAll;

class QueryBuilder
{

    protected $entity;
    protected $mappers;

    public function __construct(Entity $entity, array $mappers)
    {
        $this->entity = $entity;
        $this->mappers = $mappers;
    }

    /**
     * Count
     *
     * @return \UniMapper\Query\Countl
     */
    public function count()
    {
        return new Count($this->entity, $this->mappers);
    }

    /**
     * Find all records
     *
     * @return \UniMapper\Query\FindAll
     */
    public function findAll()
    {
        return new FindAll($this->entity, $this->mappers, func_get_args());
    }

    /**
     * Find single record
     *
     * @param mixed $primaryValue Primary property value
     *
     * @return \UniMapper\Query\FindOne
     */
    public function findOne($primaryValue)
    {
        return new FindOne($this->entity, $this->mappers, $primaryValue);
    }

}