<?php

namespace UniMapper\Tests\Fixtures\Query;

class Custom extends \UniMapper\Query\Custom
{

    public function onExecute(\UniMapper\Adapter\IAdapter $adapter)
    {
        return "foo";
    }

}