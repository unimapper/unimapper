<?php

namespace UniMapper\Exception;

class AdapterException extends \UniMapper\Exception
{

    /** @var mixed */
    protected $query;

    /**
     * @param string          $message
     * @param int             $query
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        $query,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

}