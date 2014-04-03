<?php

namespace UniMapper\Tests\Fixtures\Query;

class Simple extends \UniMapper\Query
{
    public function executeSimple()
    {
        throw new \Exception("You should  mock here!");
    }

    public function executeHybrid()
    {
        $this->executeSimple();
    }
}
