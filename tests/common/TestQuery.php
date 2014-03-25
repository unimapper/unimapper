<?php

class TestQuery extends UniMapper\Query
{
    public function onExecute()
    {
        throw new \Exception("You should  mock here!");
    }
}
