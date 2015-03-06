<?php

namespace UniMapper\Exception;

class InvalidArgumentException extends \UniMapper\Exception
{

    /** @var mixed */
    private $value;

    /**
     * @param string $message
     * @param mixed  $value   Given value
     */
    public function __construct($message, $value = null)
    {
        parent::__construct($message);
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}