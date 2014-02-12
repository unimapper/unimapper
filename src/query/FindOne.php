<?php

namespace UniMapper\Query;

/**
 * Find single item as query object
 */
class FindOne extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    public $primaryProperty;
    public $selection = array();

    /**
     * Constructor
     *
     * @param \UniMapper\Entity $entity          Output entity
     * @param mixed           $primaryProperty Primary property name
     *
     * @return \UniMapper\Find
     *
     * @todo Implement entity primary keys first
     */
    public function __construct($entityClass, $primaryProperty)
    {
        parent::__construct($entityClass);
        $this->primaryProperty = $primaryProperty;
    }

}