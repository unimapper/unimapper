<?php

namespace UniMapper;

class Logger
{

    protected $queries = array();

    public function logQuery(\UniMapper\Query $query)
    {
        $this->queries[] = $query;
    }

    public function getQueries()
    {
        return $this->queries;
    }

}