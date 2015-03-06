<?php

namespace UniMapper\Adapter;

interface IQuery
{

    public function setConditions(array $conditions);

    public function setAssociations(array $associations);

    public function getRaw();

}