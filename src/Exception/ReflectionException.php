<?php

namespace UniMapper\Exception;

class ReflectionException extends \UniMapper\Exception
{

    /** @var string */
    protected $class;

    /** @var string $definition Docblock definition */
    protected $definition;

    public function __construct(
        $message,
        $class,
        $definition = null,
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->class = $class;
        $this->definition = $definition;
    }

    /**
     * Get path to entity file
     *
     * @return string|false False if part of PHP core or PHP extension
     */
    public function getEntityPath()
    {
        $reflection = new \ReflectionClass($this->class);
        return $reflection->getFileName();
    }

    /**
     * Get problematic entity line number
     *
     * @return integer
     */
    public function getEntityLine()
    {
        if ($this->definition) {
            foreach (file($this->getEntityPath(), FILE_IGNORE_NEW_LINES)
                as $lineNumber => $line
            ) {
                if (strpos($line, $this->definition) !== false) {
                    return $lineNumber + 1;
                }
            }
        }
        return 0;
    }

}