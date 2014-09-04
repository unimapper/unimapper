<?php

namespace UniMapper\Reflection\Entity\Property\Association;

class BelongsToOne extends BelongsToMany
{

    protected $expression = "1:1\s*=\s*(.*)";

}