<?php

namespace UniMapper\Query;

use UniMapper\Entity;

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

}