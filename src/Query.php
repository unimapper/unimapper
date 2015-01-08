<?php

namespace UniMapper;

use UniMapper\Reflection,
    UniMapper\Exception\QueryException;

abstract class Query
{

    /** @var \UniMapper\Reflection\Entity */
    protected $entityReflection;

    public function __construct(Reflection\Entity $reflection)
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

}