<?php

namespace UniMapper\Query;

interface IQuery
{

    public function onExecute(\UniMapper\Mapper $mapper);

}