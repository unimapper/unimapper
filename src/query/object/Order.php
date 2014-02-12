<?php

namespace UniMapper\Query\Object;

/**
 * Order object defines sorting used in mappers.
 */
class Order
{

    /** @var string */
    public $propertyName;

    /** @var string */
    public $asc = false;

    /** @var boolean */
    public $desc = false;

    /**
     * Constructor
     *
     * @param string $propertyName
     * @param string $direction
     *
     * @return void
     */
    public function __construct($propertyName)
    {
        $this->operator = $propertyName;
    }

}