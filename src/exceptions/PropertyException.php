<?php

namespace UniMapper\Exceptions;

/**
 * Throws when wrong property definition detected.
 */
class PropertyException extends \Exception
{

    /** @var \ReflectionClass $entityReflection Entity reflection */
    protected $entityReflection;

    /** @var string $definition Property definition */
    protected $definition;

    /**
     * Constructor
     *
     * @param string           $message          Message
     * @param \ReflectionClass $entityReflection Entity reflection
     * @param string           $definition       Definition
     */
    public function __construct($message,
        \ReflectionClass $entityReflection, $definition = null
    ) {
        parent::__construct($message, 0);
        $this->entityReflection = $entityReflection;
        $this->definition = (string) $definition;
    }

    /**
     * Get path to entity file
     *
     * @return string|false False if part of PHP core or PHP extension
     */
    public function getEntityPath()
    {
        return $this->entityReflection->getFileName();
    }

    /**
     * Get problematic entity line number
     *
     * @return integer
     */
    public function getEntityLine()
    {
        if ($this->definition) {
            foreach (file($this->getEntityPath()) as $lineNumber => $line) {
                if (strpos($line, $this->definition) !== false) {
                   return $lineNumber + 1;
                }
            }
        }
        return 0;
    }

}