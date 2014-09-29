<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class OneToOne extends OneToMany
{

    protected $expression = "1:1\s*=\s*(.*)";

}