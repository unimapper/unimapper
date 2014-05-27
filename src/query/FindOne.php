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
        $result = $mapper->findOne(
            $this->entityReflection->getMapperReflection()->getResource(),
            $this->entityReflection->getPrimaryProperty()->getMappedName(),
            $this->primaryValue
        );

        if (!$result) {
            return false;
        }

        return $mapper->mapEntity($this->entityReflection->getClassName(), $result);
    }

}