<?php

namespace UniMapper\Tests\Fixtures\Query;

class Custom extends \UniMapper\Query
{

    protected function onExecute(\UniMapper\Connection $connection)
    {
        return "foo";
    }

}