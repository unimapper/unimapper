<?php

namespace UniMapper\Adapter;

interface IAdapter
{

    /**
     * @param string $resource
     *
     * @return IQuery
     */
    public function createDelete($resource);

    /**
     * @param string $resource
     * @param string $column
     * @param mixed  $primaryValue
     *
     * @return IQuery
     */
    public function createDeleteOne($resource, $column, $primaryValue);

    /**
     * @param string $resource
     * @param string $column
     * @param mixed  $primaryValue
     *
     * @return IQuery
     */
    public function createSelectOne($resource, $column, $primaryValue);

    /**
     * @param string $resource
     * @param array  $selection
     * @param array  $orderBy
     * @param int    $limit
     * @param int    $offset
     *
     * @return IQuery
     */
    public function createSelect($resource, array $selection = [], array $orderBy = [], $limit = 0, $offset = 0);

    /**
     * @param string $resource
     *
     * @return IQuery
     */
    public function createCount($resource);

    /**
     * @param string $resource
     * @param array  $values
     * @param string $primaryName
     *
     * @return IQuery
     */
    public function createInsert($resource, array $values, $primaryName = null);

    /**
     * @param string $resource
     * @param array  $values
     *
     * @return IQuery
     */
    public function createUpdate($resource, array $values);

    /**
     * @param string $resource
     * @param string $column
     * @param mixed  $primaryValue
     * @param array  $values
     *
     * @return IQuery
     */
    public function createUpdateOne($resource, $column, $primaryValue, array $values);

    /**
     * @param string $sourceResource
     * @param string $joinResource
     * @param string $targetResource
     * @param string $joinKey
     * @param string $referencingKey
     * @param mixed  $primaryValue
     * @param array  $keys
     *
     * @return IQuery
     */
    public function createManyToManyAdd($sourceResource, $joinResource, $targetResource, $joinKey, $referencingKey, $primaryValue, array $keys);

    /**
     * @param string $sourceResource
     * @param string $joinResource
     * @param string $targetResource
     * @param string $joinKey
     * @param string $referencingKey
     * @param mixed  $primaryValue
     * @param array  $keys
     *
     * @return IQuery
     */
    public function createManyToManyRemove($sourceResource, $joinResource, $targetResource, $joinKey, $referencingKey, $primaryValue, array $keys);

    /**
     * @param IQuery
     *
     * @return mixed
     */
    public function execute(IQuery $query);

}