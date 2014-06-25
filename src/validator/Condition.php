<?php

namespace UniMapper\Validator;

class Condition
{

    protected $validation;
    protected $validator;

    public function __construct(
        callable $validation,
        \UniMapper\Validator $parent
    ) {
        $this->validation = $validation;
        $this->validator = new \UniMapper\Validator($parent->getEntity(), $parent);
        $this->validator->on($parent->getProperty()->getName());
    }

    public function getProperty()
    {
        return $this->validator->getProperty();
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function validate()
    {
        $definition = $this->validation;
        return (bool) $definition(
            $this->validator
                ->getEntity()
                ->{$this->validator->getProperty()->getName()}
        );
    }

}