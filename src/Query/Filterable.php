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
        if (empty($filter)) {
            $this->filter = $filter;
        }

        try {

            $this->filter = Entity\Filter::merge(
                $this->entityReflection,
                $this->filter,
                $filter
            );
        } catch (Exception\FilterException $e) {
            throw new Exception\QueryException($e->getMessage());
        }
        return $this;
    }

    public function where(array $filter)
    {
        try {

            Entity\Filter::merge(
                $this->entityReflection,
                $this->filter,
                $filter
            );
        } catch (Exception\FilterException $e) {
            throw new Exception\QueryException($e->getMessage());
        }
        return $this;
    }

}