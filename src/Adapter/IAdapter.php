<?php

namespace UniMapper\Adapter;

interface IAdapter
{

    public function createDelete($resource);

    public function createDeleteOne($resource, $column, $primaryValue);

    public function createSelectOne($resource, $column, $primaryValue);

    public function createSelect($resource, array $selection = [], array $orderBy = [], $limit = 0, $offset = 0);

    public function createCount($resource);

    public function createInsert($resource, array $values, $primaryName = null);

    public function createUpdate($resource, array $values);

    public function createUpdateOne($resource, $column, $primaryValue, array $values);

    public function createManyToManyAdd($sourceResource, $joinResource, $targetResource, $joinKey, $referencingKey,  $primaryValue, array $keys);

    public function createManyToManyRemove($sourceResource, $joinResource, $targetResource, $joinKey, $referencingKey, $primaryValue, array $keys);

    public function execute(IQuery $query);

}