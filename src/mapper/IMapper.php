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
     * Insert
     *
     * @param \UniMapper\Query\Insert $query Query
     */
    public function insert(\UniMapper\Query\Insert $query);

    /**
     * Update
     *
     * @param \UniMapper\Query\Update $query Query
     */
    public function update(\UniMapper\Query\Update $query);

    /**
     * Custom query
     *
     * @param \UniMapper\Query\Custom $query Query
     */
    public function custom(\UniMapper\Query\Custom $query);
}