<?php

namespace UniMapper;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mapper.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = array();

    /**
     * Constructor
     *
     * @param \UniMapper\Mapper $mapper Orm mapper
     *
     * @return void
     */
    public function __construct(\UniMapper\Mapper $mapper = null)
    {
        $this->addMapper($mapper);
    }

    public function addMapper(\UniMapper\Mapper $mapper)
    {
        $this->mappers[] = $mapper;
    }

    public function createQuery($entityClass)
    {
        return new QueryBuilder(new $entityClass, $this->mappers);
    }

}