<?php

namespace UniMapper;

use UniMapper\Exception\RepositoryException,
    UniMapper\NamingConvention as NC,
    UniMapper\Cache\ICache,
    UniMapper\Reflection;

/**
 * Repository is ancestor for every new repository. It contains common
 * parameters or methods used in its descendants. Repository is intended as a
 * mediator between your application and current adapters.
 */
abstract class Repository
{

    /** @var array $adapters Registered adapters */
    private $adapters = [];

    /** @var \UniMapper\Logger $logger */
    private $logger;

    /** @var \UniMapper\Cache\ICache $cache */
    private $cache;

    /** @var array $customQueries Registered custom queries */
    private $customQueries = [];

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
            throw new RepositoryException(
                "Entity must be instance of ". $requiredClass . "!"
            );
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimaryProperty()) {
            throw new RepositoryException(
                "Can not save entity without primary property!"
            );
        }

        $primaryName = $reflection->getPrimaryProperty()->getName();
        $primaryValue = $entity->{$primaryName};

        if ($primaryValue === null) {
            $entity->{$primaryName} = $this->insert($entity);
        } else {
            $this->update($entity, $primaryValue);
        }
    }

    /**
     * Create a new record
     *
     * @param \UniMapper\Entity $entity
     *
     * @return mixed
     *
     * @throws Exception\ValidatorException
     */
    public function insert(Entity $entity)
    {
        if (!$entity->getValidator()->validate()) {
            throw new Exception\ValidatorException($entity->getValidator());
        }

        $values = $entity->getData();

        // Prevent to force empty primary property
        if ($entity->getReflection()->hasPrimaryProperty()) {

            $primaryName = $entity->getReflection()
                ->getPrimaryProperty()
                ->getName();

            if (empty($values[$primaryName])) {
                unset($values[$primaryName]);
            }
        }

        $primaryValue = $this->query()->insert($values)->execute();
        $this->_saveAssociations($primaryValue, $entity);

        return $primaryValue;
    }

    /**
     * Update record
     *
     * @param \UniMapper\Entity $entity
     * @param mixed             $primaryValue
     *
     * @throws Exception\ValidatorException
     */
    public function update(Entity $entity, $primaryValue)
    {
        if (!$entity->getValidator()->validate()) {
            throw new Exception\ValidatorException($entity->getValidator());
        }

        $values = $entity->getData();

        $this->query()->updateOne($primaryValue, $values)->execute();
        $this->_saveAssociations($primaryValue, $entity);
    }

    private function _saveAssociations($primaryValue, Entity $entity)
    {
        foreach ($entity->getAssociated() as $association) {
            $this->query()->associate($primaryValue, $association)->execute();
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
            throw new RepositoryException(
                "Entity must be instance of ". $requiredClass . "!"
            );
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimaryProperty()) {
            throw new RepositoryException(
                "Can not delete entity without primary property!"
            );
        }

        $primaryName = $reflection->getPrimaryProperty()->getName();

        $this->query()->deleteOne($entity->{$primaryName})->execute();
    }

    public function count($filter = [])
    {
        $query = $this->query()->count();

        foreach ($filter as $rule) {
            $query->where($rule[0], $rule[1], $rule[2]);
        }

        return $query->execute();
    }

    /**
     * Create new entity
     *
     * @param mixed  $values Iterable value like array or stdClass object
     * @param string $name   Entity name, default is current related entity
     *
     * @return \UniMapper\Entity
     */
    public function createEntity($values = null, $name = null)
    {
        if ($name === null) {
            $name = $this->getName();
        }

        return $this->getEntityReflection(
            NC::nameToClass($name, NC::$entityMask)
        )->createEntity($values);
    }

    /**
     * Create new entity collection
     *
     * @param string $name Entity name, default is current related entity
     *
     * @return \UniMapper\Entity
     */
    public function createCollection($values = null, $name = null)
    {
        // Get entity class
        if ($name === null) {
            $name = $this->getName();
        }
        $class = NC::nameToClass($name, NC::$entityMask);

        // Create empty collection
        $collection = new EntityCollection($this->getEntityReflection($class));

        // Add values
        if ($values) {

            foreach ($values as $item) {

                if (!$item instanceof $class) {
                    $item = $this->createEntity($item, $name);
                }
                $collection[] = $item;
            }
        }

        return $collection;
    }

    public function find(array $filter = [], array $orderBy = [], $limit = 0,
        $offset = 0, array $associate = []
    ) {
        $query = $this->query()->find();

        foreach ($filter as $rule) {
            $query->where($rule[0], $rule[1], $rule[2]);
        }

        foreach ($orderBy as $orderByRule) {
            $query->orderBy($orderByRule[0], $orderByRule[1]);
        }

        if ($associate) {
            call_user_func_array([$query, "associate"], $associate);
        }

        return $query->limit($limit)->offset($offset)->execute();
    }

    public function findOne($primaryValue, array $associate = [])
    {
        $query = $this->query()->findOne($primaryValue);

        if ($associate) {
            call_user_func_array([$query, "associate"], $associate);
        }

        return $query->execute();
    }

    /**
     * Get registered adapter
     *
     * @param string $name Adapter name
     *
     * @return \UniMapper\Adapter
     *
     * @throws RepositoryException
     */
    protected function getAdapter($name)
    {
        if (!isset($this->adapters[$name])) {
            throw new RepositoryException("Adapter '" . $name . "' not found!");
        }
        return $this->adapters[$name];
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

    /**
     * Get entity reflection
     *
     * @param string $class
     */
    protected function getEntityReflection($class = null)
    {
        if ($class === null) {
            $class = NC::nameToClass($this->getName(), NC::$repositoryMask);
        }

        if ($this->cache) {

            $reflection = $this->cache->load($class);
            if (!$reflection) {

                $reflection = new Reflection\Entity($class);

                $this->cache->save(
                    $class,
                    $reflection,
                    [
                        ICache::FILES => $reflection->getRelatedFiles(
                            [$reflection->getFileName()]
                        ),
                        ICache::TAGS => [ICache::TAG_REFLECTION]
                    ]
                );
            }
            return $reflection;
        }

        return $reflection = new Reflection\Entity($class);
    }

    public function getName()
    {
        return NC::classToName(get_called_class(), NC::$repositoryMask);
    }

    public function setCache(Cache\ICache $cache)
    {
        $this->cache = $cache;
    }

    public function setLogger(\UniMapper\Logger $logger)
    {
        $this->logger = $logger;
    }

    public function registerAdapter(\UniMapper\Adapter $adapter)
    {
        $this->adapters[$adapter->getName()] = $adapter;
    }

    public function registerCustomQuery($class)
    {
        $class = (string) $class;
        if (!is_subclass_of($class, "UniMapper\Query\Custom")) {
            throw new RepositoryException(
                "Registered custom query must be instance of Unimapper\Query\Custom!"
            );
        }
        $this->customQueries[] = $class;
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
     * @throws \UniMapper\Exception\RepositoryException
     *
     * @todo should be private
     */
    protected function queryOn($name)
    {
        $entityClass = NC::nameToClass($name, NC::$entityMask);
        if (!is_subclass_of($entityClass, "UniMapper\Entity")) {
            throw new RepositoryException(
                "Entity with name '" . $name . "' and class '" . $entityClass
                . "' not found!"
            );
        }

        $entityReflection = $this->getEntityReflection($entityClass);

        $queryBuilder = new QueryBuilder(
            $entityReflection,
            $this->adapters,
            $this->cache,
            $this->logger
        );

        foreach ($this->customQueries as $class) {
            $queryBuilder->registerQuery($class);
        }

        return $queryBuilder;
    }

    public function getLogger()
    {
        return $this->logger;
    }

}