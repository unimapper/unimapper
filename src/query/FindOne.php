<?php

namespace UniMapper\Query;

use UniMapper\Query\IConditionable,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\QueryException,
    UniMapper\Mapper,
    UniMapper\Reflection;

class FindOne extends \UniMapper\Query implements IConditionable
{

    /** @var mixed */
    public $primaryValue;

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, $primaryValue)
    {
        parent::__construct($entityReflection, $mapper);

        try {
            $entityReflection->getPrimaryProperty()->validateValue($primaryValue);
        } catch (PropertyTypeException $exception) {
            throw new QueryException($exception->getMessage());
        }

        $this->primaryValue = $primaryValue;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        return $mapper->findOne($this);
    }

}