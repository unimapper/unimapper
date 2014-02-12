<?php

namespace UniMapper\Query\Object;

/**
 * Condition object unifies condition definition used in mappers.
 */
class Condition
{

    /** @var string */
    private $expression;

    /** @var mixed */
    private $value;

    /** @var string */
    private $operator;

    /** @var array */
    private $allowedOperators = array("=", "<", ">", "<>", ">=", "<=", "IS", "IS NOT", "!=", "LIKE", "COMPARE", "IN");

    /**
     * Constructor
     *
     * @param string $expression
     * @param string $operator
     * @param string $value
     *
     * @return void
     *
     * @throws \UniMapper\Exceptions\InvalidConditionException
     */
    public function __construct($expression, $operator, $value)
    {
        if (!in_array($operator, $this->allowedOperators)) {
            throw new \UniMapper\Exceptions\InvalidConditionException(
                "Not allowed operator " . $operator . "! Allowed are only "
                . implode(",", $this->allowedOperators)
            );
        }
        $this->operator = $operator;
        $this->expression = $expression;
        $this->value = $value;
    }

    /**
     * Getter for expression
     *
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Getter for value
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Getter for operator
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

}