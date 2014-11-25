<?php

namespace UniMapper\Profiler;

class Result
{

    public $adapterQueries = [];
    public $elapsed;
    public $query;
    public $result;

    public function __construct(array $adapterQueries, $elapsed = 0, $result = null, \UniMapper\Query $query = null)
    {
        $this->adapterQueries = $adapterQueries;
        $this->elapsed = $elapsed;
        $this->result = $result;
        $this->query = $query;
    }

}