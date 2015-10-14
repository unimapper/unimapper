<?php

namespace UniMapper\Exception;

class AnnotationException extends \UniMapper\Exception
{

    /** @var string */
    protected $definition;

    /**
     * @param string          $message
     * @param mixed|null      $definition
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(
        $message,
        $definition = null,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->definition;
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

}