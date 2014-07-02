<?php

namespace UniMapper;

use UniMapper\Exceptions\RepositoryException,
    UniMapper\NamingConvention as NC,
    UniMapper\Reflection;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current mappers.
 */
abstract class Repository
{

    /** @var array $mappers Registered mappers */
    protected $mappers = [];

    /** @var \UniMapper\Logger $logger */
    private $logger;

    /** @var \UniMapper\Cache $cache */
    private $cache;

    /**
     * Insert/update entity
     *
     * @param \UniMapper\Entity $entity
     *
     * @throws RepositoryException
     */
    public function save(Entity $entity)
    {
        $requiredClass = NC::nameToClass($this->getEntityName(), NC::$entityMask);
        if (!$entity instanceof $requiredClass) {
            throw new RepositoryException("Entity must be instance of ". $requiredClass . "!");
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimaryProperty()) {
            throw new RepositoryException("Can not save entity without primary property!");
        }

        $primaryName = $reflection->getPrimaryProperty()->getName();
        $primaryValue = $entity->{$primaryName};

        if ($primaryValue === null) {
            // Insert
            $entity->{$primaryName} = $this->query()->insert($entity->getData())->execute();
        } else {
            // Update
            $this->query()->updateOne($primaryValue, $entity->getData())->execute();
        }
    }

    /**
     * Delete single entity
     *
     * @param \UniMapper\Entity $entity
     *
     * @throws RepositoryException
     */
    public function delete(Entity $entity)
    {
        $requiredClass = NC::nameToClass($this->getEntityName(), NC::$entityMask);
        if (!$entity instanceof $requiredClass) {
            throw new RepositoryException("Entity must be instance of ". $requiredClass . "!");
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimaryProperty()) {
            throw new RepositoryException("Can not delete entity without primary property!");
        }

        $primaryName = $reflection->getPrimaryProperty()->getName();
        $primaryValue = $entity->{$primaryName};
        if ($primaryValue === null) {
            throw new RepositoryException("Primary value in entity '" . $this->getEntityName() . "' must be set!");
        }

        $this->query()->delete()->where($primaryName, "=", $primaryValue)->execute();
    }

    /**
     * Create new entity
     *
     * @param mixed  $values Iterable value like array or stdClass object
     * @param string $name   Entity name, default is entity related to current repository
     *
     * @return \UniMapper\Entity
     */
    public function createEntity($values = null, $name = null)
    {
        if ($name === null) {
            $name = $this->getName();
        }

        $class = NC::nameToClass($name, NC::$entityMask);
        if ($this->cache) {
            $reflection = $this->cache->loadEntityReflection($class);
        } else {
            $reflection = new Reflection\Entity($class);
        }
        return $reflection->createEntity($values);
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

    public function setCache(Cache $cache)
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
            throw new RepositoryException("Entity with name '" . $name . "' and class '" . $entityClass . "' not found!");
        }

        if ($this->cache) {
            return new QueryBuilder(
                $this->cache->loadEntityReflection($entityClass),
                $this->mappers,
                $this->logger
            );
        }

        return new QueryBuilder(
            new Reflection\Entity($entityClass),
            $this->mappers,
            $this->logger
        );
    }

    public function getLogger()
    {
        return $this->logger;
    }

}