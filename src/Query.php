<?php

namespace UniMapper;

use UniMapper\Adapter\IQuery;
use UniMapper\Adapter\IQueryWithJoins;
use UniMapper\Exception\QueryException;
use UniMapper\Query\Filterable;

abstract class Query
{

    /** @var Entity\Reflection */
    protected $entityReflection;

    public function __construct(Entity\Reflection $reflection)
    {
        if (!$reflection->hasAdapter()) {
            throw new QueryException(
                "Can not create query because entity "
                . $reflection->getClassName() . " has no adapter defined!"
            );
        }

        $this->entityReflection = $reflection;
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public static function getName()
    {
        $reflection = new \ReflectionClass(get_called_class());
        return lcfirst($reflection->getShortName());
    }

    /**
     * Executes query
     *
     * @param \UniMapper\Connection $connection
     *
     * @return mixed
     */
    final public function run(Connection $connection)
    {
        $start = microtime(true);

        foreach (QueryBuilder::getBeforeRun() as $callback) {

            // function(\UniMapper\Query $query)
            $callback($this);
        }

        $result = $this->onExecute($connection);
        foreach (QueryBuilder::getAfterRun() as $callback) {

            // function(\UniMapper\Query $query, mixed $result, int $elapsed)
            $callback($this, $result, microtime(true) - $start);
        }

        return $result;
    }
    
    protected function setQueryFilters($filter, IQuery $query, Connection $connection)
    {
        if ($filter) {
            $query->setFilter(
                $connection->getMapper()->unmapFilter(
                    $this->entityReflection,
                    $filter
                )
            );

            if ($query instanceof IQueryWithJoins) {
                $joins = $connection->getMapper()->unmapFilterJoins(
                    $this->entityReflection,
                    $filter
                );

                if ($joins) {
                    $query->setJoins($joins);
                }
            }
        }
    }

}