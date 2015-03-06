<?php

namespace UniMapper\Exception;

class AdapterException extends \UniMapper\Exception
{

    private $query;

    public function __construct($message, $query)
    {
        parent::__construct($message);
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }

}