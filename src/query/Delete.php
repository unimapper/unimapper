<?php

namespace UniMapper\Query;

/**
 * Delete query
 */
class Delete extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    public $limit = 0;

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

}