<?php

namespace UniMapper\Association;

use UniMapper\Query;

abstract class Multi extends \UniMapper\Association
{

    use Query\Limit;
    use Query\Sortable;
    use Query\Filterable;

}