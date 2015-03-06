<?php

namespace UniMapper\Exception;

class AnnotationException extends \UniMapper\Exception
{

    /** @var string */
    private $definition;

    public function __construct($message, $definition = null)
    {
        parent::__construct($message);
        $this->definition;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

}