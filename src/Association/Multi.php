<?php

namespace UniMapper\Association;

use UniMapper\Query;

abstract class Multi extends Single
{

    use Query\Limit;
    use Query\Sortable;

}