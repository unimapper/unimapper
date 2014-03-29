<?php

namespace UniMapper\Tests\Fixtures\Query;

class Conditionable extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{
    public function onExecute()
    {
        throw new \Exception("You should  mock here!");
    }
}
