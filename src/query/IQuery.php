<?php

namespace UniMapper\Query;

interface IQuery
{

    public function onExecute(\UniMapper\Adapter $adapter);

}