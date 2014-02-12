<?php

namespace UniMapper\Query;

/**
 * Insert query object
 */
class Insert extends \UniMapper\Query
{

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
        $this->entity;
    }

}