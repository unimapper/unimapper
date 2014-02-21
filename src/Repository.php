<?php

namespace UniMapper;

use UniMapper\Exceptions\RepositoryException,
    UniMapper\Reflection\EntityReflection;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mapper.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = array();

    protected $logger = null;

    public function __construct(\UniMapper\Logger $logger = null)
    {
        if ($logger) {
            $this->setLogger($logger);
        }
    }

    public function setLogger(\UniMapper\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function addMapper(\UniMapper\Mapper $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;
    }

    public function createQuery($entityClass)
    {
        if (count($this->mappers) === 0) {
            throw new RepositoryException("You must set one mapper at least!");
        }
        return new QueryBuilder(new EntityReflection($entityClass), $this->mappers, $this->logger);
    }

    public function getLogger()
    {
        return $this->logger;
    }

}