<?php

namespace UniMapper;

use UniMapper\Exceptions\RepositoryException,
    UniMapper\NamingConvention as NC,
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

    public function createEntity($name, $values = null)
    {
        $class = NC::nameToClass($name, NC::$entityMask);
        $entity = new $class($this->cache);

        if ($values !== null) {
            $entity->import($values);
        }

        return $entity;
    }

    /**
     * Get related entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->getName();
    }

    public function getName()
    {
        return NC::classToName(get_called_class(), NC::$repositoryMask);
    }

    public function setCache(ICache $cache)
    {
        $this->cache = $cache;
    }

    public function setLogger(\UniMapper\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function registerMapper(\UniMapper\Mapper $mapper)
    {
        $this->mappers[$mapper->getName()] = $mapper;
    }

    /**
     * Query on entity related to actual repository
     *
     * @return \UniMapper\QueryBuilder
     */
    public function query()
    {
        return $this->queryOn($this->getEntityName());
    }

    /**
     * Create custom query on specific entity
     *
     * @param $name Entity name
     *
     * @return \UniMapper\QueryBuilder
     *
     * @throws \UniMapper\Excpetions\RepositoryException
     */
    protected function queryOn($name)
    {
        $entityClass = NC::nameToClass($name, NC::$entityMask);
        if (!is_subclass_of($entityClass, "UniMapper\Entity")) {
            throw new RepositoryException("Entity with class '" . $entityClass . "' not found!");
        }

        if (count($this->mappers) === 0) {
            throw new RepositoryException("You must set one mapper at least!");
        }

        if ($this->cache) {

            $key = "entity-" . $entityClass;
            $reflection = $this->cache->load($key);
            if (!$reflection) {
                $reflection = new Reflection\Entity($entityClass);
                $this->cache->save($key, $reflection, $reflection->getFileName());
            }
        } else {
            $reflection = new Reflection\Entity($entityClass);
        }

        $mapperName = $reflection->getMapperReflection()->getName();
        if (!$this->mappers[$mapperName]) {
            throw new RepositoryException("Mapper with name '" . $mapperName . "' not found!");
        }

        return new QueryBuilder($reflection, $this->mappers[$mapperName], $this->logger);
    }

    public function getLogger()
    {
        return $this->logger;
    }

}