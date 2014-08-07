<?php

namespace UniMapper\Adapter;

interface IAdapter
{

    public function count($resource, $conditions);

    public function delete($resource, $conditions);

    public function findOne($resource, $primaryName, $primaryValue, array $associations = []);

    public function findAll($resource, $selection = null, $conditions = null, $orderBy = null, $limit = 0, $offset = 0, array $associations = []);

    public function insert($resource, array $values);

    public function update($resource, array $values, $conditions = null);

    public function updateOne($resource, $primaryName, $primaryValue, array $values);

}