<?php

namespace UniMapper\Query;

interface IQuery
{

    public function executeSimple(\UniMapper\Mapper $mapper);
    public function executeHybrid();

}