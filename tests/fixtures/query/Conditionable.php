<?php

namespace UniMapper\Tests\Fixtures\Query;

class Conditionable extends \UniMapper\Query implements \UniMapper\Query\IConditionable
{

    public function executeSimple(\UniMapper\Mapper $mapper)
    {
        throw new \Exception("You should  mock here!");
    }

    public function executeHybrid()
    {
        throw new \Exception("You should  mock here!");
    }

}
