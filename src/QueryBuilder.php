<?php

namespace UniMapper;

use UniMapper\Reflection\EntityReflection,
    UniMapper\Entity,
    UniMapper\Query\Insert,
    UniMapper\Query\FindOne,
    UniMapper\Query\Count,
    UniMapper\Query\Update,
    UniMapper\Query\Custom,
    UniMapper\Query\FindAll;

class QueryBuilder
{

    protected $entityReflection;
    protected $mappers;
    protected $logger;

    public function __construct(EntityReflection $entityReflection, array $mappers, Logger $logger = null)
    {
        $this->entityReflection = $entityReflection;
        $this->mappers = $mappers;
        $this->logger = $logger;
    }

    protected function logQuery(Query $query)
    {
        if ($this->logger) {
            $this->logger->logQuery($query);
        }
    }

    /**
     * Count
     *
     * @return \UniMapper\Query\Countl
     */
    public function count()
    {
        $query = new Count($this->entityReflection, $this->mappers);
        $this->logQuery($query);
        return $query;
    }

    /**
     * Find all records
     *
     * @return \UniMapper\Query\FindAll
     */
    public function findAll()
    {
        $query = new FindAll($this->entityReflection, $this->mappers, func_get_args());
        $this->logQuery($query);
        return $query;
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
        $query = new FindOne($this->entityReflection, $this->mappers, $primaryValue);
        $this->logQuery($query);
        return $query;
    }

    /**
     * Custom query
     *
     * @param string $mapperName
     *
     * @return \UniMapper\Query\Custom
     *
     * @throws \Exception
     */
    public function custom($mapperName)
    {
        $query = new Custom($this->entityReflection, $this->mappers, $mapperName);
        $this->logQuery($query);
        return $query;
    }

    public function insert(Entity $entity)
    {
        $query = new Insert($this->entityReflection, $this->mappers, $entity);
        $this->logQuery($query);
        return $query;
    }

    public function update(Entity $entity)
    {
        $query = new Update($this->entityReflection, $this->mappers, $entity);
        $this->logQuery($query);
        return $query;
    }

}