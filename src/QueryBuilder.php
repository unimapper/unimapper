<?php

namespace UniMapper;

/**
 * @method \UniMapper\Query\Select    select()
 * @method \UniMapper\Query\SelectOne selectOne($primaryValue)
 * @method \UniMapper\Query\Insert    insert(array $data)
 * @method \UniMapper\Query\Update    update(array $data)
 * @method \UniMapper\Query\UpdateOne updateOne($primaryValue, array $data)
 * @method \UniMapper\Query\Delete    delete()
 * @method \UniMapper\Query\DeleteOne deleteOne($primaryValue)
 * @method \UniMapper\Query\Count     count()
 */
class QueryBuilder
{

    /** @var array */
    private static $queries = [
        "count" => "UniMapper\Query\Count",
        "raw" => "UniMapper\Query\Raw",
        "delete" => "UniMapper\Query\Delete",
        "deleteOne" => "UniMapper\Query\DeleteOne",
        "select" => "UniMapper\Query\Select",
        "selectOne" => "UniMapper\Query\SelectOne",
        "insert" => "UniMapper\Query\Insert",
        "update" => "UniMapper\Query\Update",
        "updateOne" => "UniMapper\Query\UpdateOne"
    ];

    /** @var array */
    private static $afterRun = [];

    /** @var array */
    private static $beforeRun = [];

    /** @var Entity\Reflection $entityReflection */
    private $entityReflection;

    /**
     * @param mixed $entity Entity object, class or name
     */
    public function __construct($entity)
    {
        $this->entityReflection = Entity\Reflection\Loader::load($entity);
    }

    public function __call($name, $arguments)
    {
        if (!isset(self::$queries[$name])) {
            throw new Exception\InvalidArgumentException(
                "Query with name " . $name . " does not exist!"
            );
        }

        array_unshift($arguments, $this->entityReflection);

        $class = new \ReflectionClass(self::$queries[$name]);
        return $class->newInstanceArgs($arguments);
    }

    public static function beforeRun(callable $callback)
    {
        self::$beforeRun[] = $callback;
    }

    public static function afterRun(callable $callback)
    {
        self::$afterRun[] = $callback;
    }

    public static function getBeforeRun()
    {
        return self::$beforeRun;
    }

    public static function getAfterRun()
    {
        return self::$afterRun;
    }

    /**
     * Register custom query
     *
     * @param string $class
     */
    public static function registerQuery($class)
    {
        self::$queries[$class::getName()] = $class;
    }

}