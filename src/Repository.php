<?php

namespace UniMapper;

use UniMapper\Exceptions\RepositoryException,
    UniMapper\Cache\ICache,
    UniMapper\Reflection;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mappers.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = array();

    private $logger;

    private $cache;

    public function detectEntityClass()
    {
        $name = explode("\\", get_called_class());
        $name = end($name);
        return substr($name, 0, strrpos($name, "Repository"));
    }

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

    protected function createQuery($entityClass = "")
    {
        if (empty($entityClass)) {
            $entityClass = $this->detectEntityClass();
        }
        if (!$entityClass) {
            throw new RepositoryException("Query must be called on some entity class in repository " .  get_class($this) . "!");
        }
        if (!is_subclass_of($entityClass, "UniMapper\Entity")) {
            throw new RepositoryException("Can not set class '" . $entityClass . "' as default entity in repository " .  get_class($this) . "!");
        }
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