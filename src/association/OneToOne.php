<?php

namespace UniMapper\Association;

class OneToOne extends Single
{

    protected $expression = "1:1\s*=\s*(.*)";

}