<?php

namespace UniMapper\Query;

use UniMapper\EntityCollection,
    UniMapper\Entity;

/**
 * Find single item as query object
 */
class FindOne extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    public $primaryValue = array();

    public function __construct(Entity $entity, array $mappers, $primaryValue)
    {
        parent::__construct($entity, $mappers);
        $this->primaryValue = $primaryValue;
    }

    public function onExecute()
    {
        $result = false;
        $entityMappers = $this->entityReflection->getMappers();

        foreach ($this->mappers as $mapper) {

            if (isset($entityMappers[get_class($mapper)])) {

                $data = $mapper->findOne($this);
                if ($data === false) {
                    continue;
                }

                if ($result instanceof EntityCollection && $data instanceof EntityCollection) {
                    // There are some results from previous queries, so merge it
                    $result->merge($data);
                } else {
                    $result = $data;
                }
            }
        }

        return $result;
    }

}