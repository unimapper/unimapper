<?php

namespace UniMapper\Adapter;

interface IQuery
{

    public function setFilter(array $filter);

    public function setAssociations(array $associations);

    public function getRaw();

}