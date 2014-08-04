<?php

namespace UniMapper\Query;

use UniMapper\Query\IConditionable,
    UniMapper\Exception\QueryException,
    UniMapper\Reflection;

class FindOne extends \UniMapper\Query implements IConditionable
{

    /** @var mixed */
    public $primaryValue;

    /** @var array */
    private $associations = [
        "local" => [],
        "remote" => []
    ];

    public function __construct(Reflection\Entity $entityReflection, array $adapters, $primaryValue)
    {
        parent::__construct($entityReflection, $adapters);

        if (!$entityReflection->hasPrimaryProperty()) {
            throw new QueryException("Can not use findOne() on entity without primary property!");
        }

        $entityReflection->getPrimaryProperty()->validateValue($primaryValue);

        $this->primaryValue = $primaryValue;
    }

    public function associate($propertyName)
    {
        foreach (func_get_args() as $name) {

            if (!isset($this->entityReflection->getProperties()[$name])) {
                throw new QueryException("Property '" . $name . "' not defined!");
            }

            $property = $this->entityReflection->getProperties()[$name];
            if (!$property->isAssociation()) {
                throw new QueryException("Property '" . $name . "' is not defined as association!");
            }

            $association = $property->getAssociation();
            if ($association->isRemote()) {
                $this->associations["remote"][$name] = $association;
            } else {
                $this->associations["local"][$name] = $association;
            }
        }

        return $this;
    }

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        $result = $adapter->findOne(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $this->entityReflection->getPrimaryProperty()->getMappedName(),
            $this->primaryValue,
            $this->associations["local"]
        );

        if (!$result) {
            return false;
        }

        return $adapter->getMapping()->mapEntity($this->entityReflection->getClassName(), $result);
    }

}