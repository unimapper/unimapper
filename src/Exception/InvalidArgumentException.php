<?php

namespace UniMapper\Exception;

class InvalidArgumentException extends \UniMapper\Exception
{

    /** @var mixed */
    protected $value;

    /**
     * @param string     $message
     * @param mixed      $value
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct(
        $message,
        $value = null,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->value = $value;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

}