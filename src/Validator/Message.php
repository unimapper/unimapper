<?php

namespace UniMapper\Validator;

class Message implements \JsonSerializable
{

    /** @var array $severityText */
    private $severityText = [
        Rule::ERROR => "error",
        Rule::WARNING => "warning",
        Rule::INFO => "info",
        Rule::DEBUG => "debug"
    ];

    /** @var string $text */
    private $text;

    /** @var integer $severity */
    private $severity;

    /** @var  array */
    private $path;

    public function __construct($text, $severity, $path = [])
    {
        $this->text = $text;
        $this->severity = $severity;
        $this->path = $path;
    }

    public function jsonSerialize()
    {
        return [
            "text" => $this->text,
            "severity" => $this->getSeverity(),
            "path" => $this->getPath(),
        ];
    }

    public function getText()
    {
        return $this->text;
    }

    public function getSeverity()
    {
        return $this->severityText[$this->severity];
    }

    public function getPath()
    {
        return $this->path;
    }

}