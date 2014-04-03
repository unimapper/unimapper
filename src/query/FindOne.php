<?php

namespace UniMapper\Query;

use UniMapper\Entity,
    UniMapper\Query\IConditionable,
    UniMapper\Exceptions\PropertyTypeException,
    UniMapper\Exceptions\QueryException,
    UniMapper\Reflection;

class FindOne extends \UniMapper\Query implements IConditionable
{

    /** @var mixed */
    public $primaryValue;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, $primaryValue)
    {
        parent::__construct($entityReflection, $mappers);

        try {
            $entityReflection->getPrimaryProperty()->validateValue($primaryValue);
        } catch (PropertyTypeException $exception) {
            throw new QueryException($exception->getMessage());
        }

        $this->primaryValue = $primaryValue;
    }

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        return $mapper->findOne($this);
    }

    public function executeHybrid()
    {
        $result = false;

        $i = 0;
        foreach ($this->entityReflection->getMappers() as $mapperName => $mapperReflection) {

            $mapper = $this->mappers[$mapperName];

            // Nothing found in previous queries
            if (!$result && $i > 0) {
                return false;
            }

            $data = $mapper->findOne($this);

            $i++;

            if ($data === false) {
                continue;
            }

            if ($result instanceof Entity && $data instanceof Entity) {
                // There are some results from previous queries, so merge it
                $result->merge($data);
            } else {
                $result = $data;
            }
        }

        return $result;
    }

}