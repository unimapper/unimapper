<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Query\IConditionable,
    UniMapper\Mapper,
    UniMapper\Reflection;

class UpdateOne extends \UniMapper\Query implements IConditionable
{

    /** @var array */
    private $values = [];

    private $primaryName;

    private $primaryValue;

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, $primaryName, $primaryValue, array $data)
    {
        parent::__construct($entityReflection, $mapper);

        // Primary value update is not allowed
        $primaryName = $entityReflection->getPrimaryProperty()->getMappedName();
        unset($data[$primaryName]);

        $this->primaryName = $primaryName;
        $this->primaryValue = $primaryValue;

        $class = $entityReflection->getClassName();
        $entity = new $class;
        $entity->import($data); // @todo easier validation

        $this->values = $mapper->unmapEntity($entity);

        // Values can not be empty
        if (empty($this->values)) {
            throw new QueryException("Nothing to update!");
        }
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getPrimaryValue()
    {
        return $this->primaryValue;
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $mapper->updateOne(
            $this->entityReflection->getMapperReflection()->getResource(),
            $this->primaryName,
            $this->primaryValue,
            $this->values
        );
    }

}