<?php

namespace UniMapper\Mapper;

/**
 * Mapper interface defines minimum required methods or parameters in every
 * new mapper.
 */
interface IMapper
{

    /**
     * Count
     *
     * @param \UniMapper\Query\Count $query
     */
    public function count(\UniMapper\Query\Count $query);

    /**
     * Delete
     *
     * @param \UniMapper\Query\Delete $query Query
     */
    public function delete(\UniMapper\Query\Delete $query);

    /**
     * Find single record
     *
     * @param \UniMapper\Query\FindOne $query Query
     */
    public function findOne(\UniMapper\Query\FindOne $query);

    /**
     * FindAll
     *
     * @param \UniMapper\Query\FindAll $query FindAll Query
     */
    public function findAll(\UniMapper\Query\FindAll $query);

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
     * Custom query
     *
     * @param \UniMapper\Query\Custom $query Query
     */
    public function custom(\UniMapper\Query\Custom $query);
}