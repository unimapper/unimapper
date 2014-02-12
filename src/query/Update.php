<?php

namespace UniMapper\Query;

/**
 * Update query object
 */
class Update extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    public $limit = 0;
    public $entity;

    /**
     * Constructor
     *
     * @param \UniMapper\Entity $entity Input entity
     *
     * @return void
     */
    public function __construct(\UniMapper\Entity $entity)
    {
        parent::__construct(get_class($entity));
        $this->entity = $entity;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

}