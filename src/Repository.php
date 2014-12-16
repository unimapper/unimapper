<?php

namespace UniMapper;

use UniMapper\NamingConvention as UNC;

abstract class Repository
{

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var \UniMapper\EntityFactory */
    protected $entityFactory;

    /** @var \UniMapper\Mapper */
    protected $mapper;

    public function __construct(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->entityFactory = $queryBuilder->getEntityFactory();
    }

    /**
     * Insert/update entity
     *
     * @param Entity $entity
     *
     * @throws Exception\RepositoryException
     */
    public function save(Entity $entity)
    {
        $requiredClass = UNC::nameToClass($this->getEntityName(), UNC::$entityMask);
        if (!$entity instanceof $requiredClass) {
            throw new Exception\RepositoryException(
                "Entity must be instance of ". $requiredClass . "!"
            );
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimary()) {
            throw new Exception\RepositoryException(
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
     * @param Entity $entity
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
        if ($entity->getReflection()->hasPrimary()) {

            $primaryName = $entity->getReflection()
                ->getPrimaryProperty()
                ->getName();

            if (empty($values[$primaryName])) {
                unset($values[$primaryName]);
            }
        }

        try {
            $primaryValue = $this->query()->insert($values)->execute();
        } catch (Exception\QueryException $e) {
            throw new Exception\RepositoryException($e->getMessage());
        }

        $this->_persistAssociations($primaryValue, $entity);

        return $primaryValue;
    }

    /**
     * Update record
     *
     * @param Entity $entity
     * @param mixed  $primaryValue
     *
     * @throws Exception\ValidatorException
     */
    public function update(Entity $entity, $primaryValue)
    {
        if (!$entity->getValidator()->validate()) {
            throw new Exception\ValidatorException($entity->getValidator());
        }

        try {

            if (!$this->query()->updateOne($primaryValue, $entity->getData())->execute()) {
                throw new Exception\RepositoryException("Entity was not successfully updated!");
            }
        } catch (Exception\QueryException $e) {
            throw new Exception\RepositoryException($e->getMessage());
        }

        $this->_persistAssociations($primaryValue, $entity);
    }

    private function _persistAssociations($primaryValue, Entity $entity)
    {
        foreach ($entity->getModifiers() as $modifier) {
            $modifier->save(
                $this->queryBuilder->getAdapters()[$entity->getReflection()->getAdapterName()],
                $this->queryBuilder->getAdapters()[$modifier->getAssociation()->getTargetAdapterName()],
                $primaryValue
            );
        }
    }

    /**
     * Delete single entity
     *
     * @param Entity $entity
     *
     * @return boolean
     */
    public function delete(Entity $entity)
    {
        $requiredClass = UNC::nameToClass($this->getEntityName(), UNC::$entityMask);
        if (!$entity instanceof $requiredClass) {
            throw new Exception\RepositoryException(
                "Entity must be instance of ". $requiredClass . "!"
            );
        }

        $reflection = $entity->getReflection();
        if (!$reflection->hasPrimary()) {
            throw new Exception\RepositoryException(
                "Can not delete entity without primary property!"
            );
        }

        $primaryName = $reflection->getPrimaryProperty()->getName();

        return $this->query()->deleteOne($entity->{$primaryName})->execute();
    }

    public function count($filter = [])
    {
        $query = $this->query()->count();

        foreach ($filter as $rule) {
            $query->where($rule[0], $rule[1], $rule[2]);
        }

        return $query->execute();
    }

    public function find(array $filter = [], array $orderBy = [], $limit = 0,
        $offset = 0, array $associate = []
    ) {
        $query = $this->query()->select();

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
        $query = $this->query()->selectOne($primaryValue);

        if ($associate) {
            call_user_func_array([$query, "associate"], $associate);
        }

        return $query->execute();
    }

    /**
     * Find records by set if primary values
     *
     * @param array $primaryValues
     *
     * @return EntityCollection
     */
    public function findPrimaries(array $primaryValues)
    {
        $entityReflection = $this->entityFactory->getEntityReflection(
            $this->getEntityName()
        );

        if (!$entityReflection->hasPrimary()) {
            throw new Exception\RepositoryException(
                "Method can not be used because entity " . $this->getEntityName()
                . " has no primary property defined!"
            );
        }

        if (empty($primaryValues)) {
            throw new Exception\InvalidArgumentException(
                "Values must be specified!"
            );
        }

        return $this->query()
            ->select()
            ->where(
                $entityReflection->getPrimaryProperty()->getName(),
                "IN",
                $primaryValues
            )
            ->execute();
    }

    /**
     * Get adapter
     *
     * @param string $name
     *
     * @return \UniMapper\Adapter
     */
    protected function getAdapter($name)
    {
        return $this->queryBuilder->getAdapters()[$name];
    }

    protected function mapToEntity($name, $values)
    {
        return $this->queryBuilder->getMapper()->mapEntity(
            $this->entityFactory->getEntityReflection($name),
            $values
        );
    }

    protected function mapToCollection($name, $values)
    {
        return $this->queryBuilder->getMapper()->mapCollection(
            $this->entityFactory->getEntityReflection($name),
            $values
        );
    }

    protected function unmapFromEntity(Entity $entity)
    {
        return $this->queryBuilder->getMapper()->unmapEntity($entity);
    }

    protected function unmapFromCollection(EntityCollection $collection)
    {
        return $this->queryBuilder->getMapper()->unmapCollection($collection);
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
        return UNC::classToName(get_called_class(), UNC::$repositoryMask);
    }

    /**
     * Query on entity related to actual repository
     *
     * @return Query
     */
    protected function query()
    {
        return new Repository\Caller(
            $this->queryBuilder,
            $this->getEntityName()
        );
    }

}