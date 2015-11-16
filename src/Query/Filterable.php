<?php

namespace UniMapper\Query;

use UniMapper\Entity;
use UniMapper\Exception;
use UniMapper\Entity\Reflection;

trait Filterable
{

    /** @var array */
    protected $filter = [];

    public function setFilter(array $filter = [])
    {
        try {
            Entity\Filter::validate($this->reflection, $filter);
        } catch (Exception\FilterException $e) {
            throw new Exception\QueryException($e->getMessage());
        }
        $this->filter = $filter;
        return $this;
    }

    public function where(array $filter)
    {
        try {
            Entity\Filter::validate($this->reflection, $filter);
        } catch (Exception\FilterException $e) {
            throw new Exception\QueryException($e->getMessage());
        }
        $this->filter = Entity\Filter::merge($this->filter, $filter);
        return $this;
    }

}