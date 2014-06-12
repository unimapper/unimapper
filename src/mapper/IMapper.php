<?php

namespace UniMapper\Mapper;

/**
 * Mapper interface defines minimum required methods or parameters in every
 * new mapper.
 */
interface IMapper
{

    /**
     * Count records by conditions
     *
     * @param string $resource
     * @param array  $conditions
     */
    public function count($resource, array $conditions);

    /**
     * Delete record by some conditions
     *
     * @param \UniMapper\Query\Delete $query
     */
    public function delete($resource, array $conditions);

    /**
     * Find single record identified by primary value
     *
     * @param string $resource
     * @param mixed  $primaryName
     * @param mixed  $primaryValue
     */
    public function findOne($resource, $primaryName, $primaryValue);

    /**
     * Find records
     *
     * @param string  $resource
     * @param array   $selection
     * @param array   $conditions
     * @param array   $orderBy
     * @param integer $limit
     * @param integer $offset
     */
    public function findAll($resource, array $selection, array $conditions, array $orderBy, $limit = 0, $offset = 0);

    /**
     * Insert should return primary value
     *
     * @param string $resource
     * @param array  $values
     */
    public function insert($resource, array $values);

    /**
     * Update data by set of conditions
     *
     * @param string $resource
     * @param array  $values
     * @param array  $conditions
     */
    public function update($resource, array $values, array $conditions);

    /**
     * Update single record
     *
     * @param string $resource
     * @param string $primaryName
     * @param mixed  $primaryValue
     * @param array  $values
     */
    public function updateOne($resource, $primaryName, $primaryValue, array $values);

    /**
     * Custom query
     *
     * @param string $resource
     * @param string $query
     * @param string $method
     * @param string $contentType
     * @param mixed  $data
     */
    public function custom($resource, $query, $method, $contentType, $data);

}