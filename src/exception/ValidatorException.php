<?php

namespace UniMapper\Exception;

class ValidatorException extends \UniMapper\Exception
{

    /** @var \UniMapper\Validator $validator */
    private $validator;

    public function __construct(\UniMapper\Validator $validator)
    {
        parent::__construct("", 0);
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