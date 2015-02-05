<?php

namespace UniMapper\Query;

use UniMapper\Reflection;
use UniMapper\NamingConvention as UNC;
use UniMapper\Cache\ICache;

class Select extends \UniMapper\Query
{

    use Conditionable;
    use Limit;
    use Selectable;
    use Sortable;

    const ASC = "asc",
          DESC = "desc";

    protected $cached = false;
    protected $cachedOptions = [];

    public function __construct(Reflection\Entity $entityReflection)
    {
        parent::__construct($entityReflection);
        $this->select(array_slice(func_get_args(), 3));
    }

    public function cached($enable = true, array $options = [])
    {
        $this->cached = (bool) $enable;
        $this->cachedOptions = $options;
        return $this;
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());
        $mapper = $connection->getMapper();
        $cache = null;

        if ($this->cached) {
            $cache = $connection->getCache();
        }

        if ($cache) {

            $cachedResult = $cache->load($this->_getQueryChecksum());
            if ($cachedResult) {
                return $mapper->mapCollection(
                    $this->entityReflection->getName(),
                    $cachedResult
                );
            }
        }

        $query = $adapter->createSelect(
            $this->entityReflection->getAdapterResource(),
            $this->createSelection(),
            $this->orderBy,
            $this->limit,
            $this->offset
        );

        if ($this->conditions) {
            $query->setConditions($this->unmapConditions($this->conditions));
        }

        if ($this->associations["local"]) {
            $query->setAssociations($this->associations["local"]);
        }

        // Execute adapter query
        $result = $adapter->execute($query);

        // Get remote associations
        if ($this->associations["remote"] && !empty($result)) {

            settype($result, "array");

            foreach ($this->associations["remote"] as $colName => $association) {

                $assocKey = $association->getKey();

                $assocValues = [];
                foreach ($result as $item) {

                    if (is_array($item)) {
                        $assocValues[] = $item[$assocKey];
                    } else {
                        $assocValues[] = $item->{$assocKey};
                    }
                }

                $associated = $association->load($connection, $assocValues);

                // Merge returned associations
                if (!empty($associated)) {

                    $result = $this->_mergeAssociated(
                        $result,
                        $associated,
                        $assocKey,
                        $colName
                    );
                }
            }
        }

        if ($cache) {

            $cachedOptions = $this->cachedOptions;

            // Add default cache tag
            if (isset($cachedOptions[ICache::TAGS])) {
                $cachedOptions[ICache::TAGS][] = ICache::TAG_QUERY; // @todo is it really array?
            } else {
                $cachedOptions[ICache::TAGS] = [ICache::TAG_QUERY];
            }

            // Cache invalidation should depend on entity changes
            if (isset($cachedOptions[ICache::FILES])) {
                $cachedOptions[ICache::FILES] += $this->entityReflection->getRelatedFiles();
            } else {
                $cachedOptions[ICache::FILES] = $this->entityReflection->getRelatedFiles();
            }

            $cache->save(
                $this->_getQueryChecksum(),
                $result,
                $cachedOptions
            );
        }

        return $mapper->mapCollection(
            $this->entityReflection->getName(),
            empty($result) ? [] : $result
        );
    }

    /**
     * Merge associated data with result
     *
     * @param array  $result
     * @param array  $associated
     * @param string $refKey
     * @param string $colName
     *
     * @return array
     */
    private function _mergeAssociated(
        array $result,
        array $associated,
        $refKey,
        $colName
    ) {
        foreach ($result as $index => $item) {

            if (is_array($item)) {
                $refValue = $item[$refKey];
            } else {
                $refValue = $item->{$refKey};
            }

            if (isset($associated[$refValue])) {

                if (is_array($result[$index])) {
                    $result[$index][$colName] = $associated[$refValue];
                } else {
                    $result[$index]->{$colName} = $associated[$refValue];
                }
            }
        }
        return $result;
    }

    /**
     * Get a unique query checksum
     *
     * @return integer
     */
    private function _getQueryChecksum()
    {
        return md5(
            serialize(
                [
                    "name" => $this->getName(),
                    "entity" => UNC::classToName(
                        $this->entityReflection->getClassName(), UNC::ENTITY_MASK
                    ),
                    "limit" => $this->limit,
                    "offset" => $this->offset,
                    "selection" => $this->selection,
                    "orderBy" => $this->orderBy,
                    "localAssociations" => array_keys($this->associations["local"]),
                    "remoteAssociations" => array_keys($this->associations["remote"]),
                    "conditions" => $this->conditions
                ]
            )
        );
    }

}