<?php

namespace UniMapper\Query;

trait Limit
{

    /** @var int */
    protected $limit;

    /** @var int */
    protected $offset;

    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

}
