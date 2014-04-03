<?php

namespace UniMapper\Tests\Fixtures\Query;

class Simple extends \UniMapper\Query
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
