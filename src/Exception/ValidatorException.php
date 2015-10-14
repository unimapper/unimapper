<?php

namespace UniMapper\Exception;

class ValidatorException extends \UniMapper\Exception
{

    /** @var \UniMapper\Validator $validator */
    protected $validator;

    /**
     * @param \UniMapper\Validator $validator
     * @param string               $message
     * @param int                  $code
     * @param \Exception|null      $previous
     */
    public function __construct(
        \UniMapper\Validator $validator,
        $message = "",
        $code = 0,
        \Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->validator = $validator;
    }

    /**
     * @return \UniMapper\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

}