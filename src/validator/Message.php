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

    public function __construct($text, $severity)
    {
        $this->text = $text;
        $this->severity = $severity;
    }

    public function jsonSerialize()
    {
        return [
            "text" => $this->text,
            "severity" => $this->getSeverity()
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

}