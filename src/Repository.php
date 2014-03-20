<?php

namespace UniMapper;

use UniMapper\Exceptions\RepositoryException,
    UniMapper\Cache\ICache,
    UniMapper\Reflection;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mapper.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = array();

    private $logger;

    private $cache;

    public function setCache(ICache $cache)
    {
        $this->cache = $cache;
    }

    public function setLogger(\UniMapper\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function addMapper(\UniMapper\Mapper $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;
    }

    protected function createQuery($entityClass)
    {
        if (count($this->mappers) === 0) {
            throw new RepositoryException("You must set one mapper at least!");
        }
        return new QueryBuilder(new Reflection\Entity($entityClass, $this->cache), $this->mappers, $this->logger);
    }

    public function getLogger()
    {
        return $this->logger;
    }

}