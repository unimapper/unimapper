<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection,
    UniMapper\Association,
    UniMapper\NamingConvention as UNC,
    UniMapper\Cache\ICache;

class Select extends Selectable
{

    const ASC = "asc",
          DESC = "desc";

    protected $limit;
    protected $offset;
    protected $orderBy = [];
    protected $selection = [];
    protected $cached = false;
    protected $cachedOptions = [];

    public function __construct(Reflection\Entity $entityReflection)
    {
        parent::__construct($entityReflection);

        $selection = array_slice(func_get_args(), 3);
        array_walk($selection, [$this, "select"]);
    }

    public function select($name)
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\QueryException(
                "Property " . $name . " is not defined on entity "
                . $this->entityReflection->getClassName() . "!"
            );
        }

        $property = $this->entityReflection->getProperty($name);
        if ($property->hasOption(Reflection\Property::OPTION_ASSOC)
            || $property->hasOption(Reflection\Property::OPTION_COMPUTED)
        ) {
            throw new Exception\QueryException(
                "Associations and computed properties can not be selected!"
            );
        }

        if (!array_search($name, $this->selection)) {
            $this->selection[] = $name;
        }

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    public function cached($enable = true, array $options = [])
    {
        $this->cached = (bool) $enable;
        $this->cachedOptions = $options;
        return $this;
    }

    public function orderBy($name, $direction = self::ASC)
    {
        if (!$this->entityReflection->hasProperty($name)) {
            throw new Exception\QueryException(
                "Invalid property name '" . $name . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== self::ASC && $direction !== self::DESC) {
            throw new Exception\QueryException("Order direction must be 'asc' or 'desc'!");
        }

        $this->orderBy[$this->entityReflection->getProperty($name)->getName(true)] = $direction;
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
            $query->setConditions($this->unmapConditions($mapper, $this->conditions));
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

    protected function addCondition($name, $operator, $value, $joiner = 'AND')
    {
        parent::addCondition($name, $operator, $value, $joiner);

        // Add properties from conditions
        if ($this->selection && !in_array($name, $this->selection)) {
            $this->selection[] = $name;
        }
    }

    protected function addNestedConditions(\Closure $callback, $joiner = 'AND')
    {
        $query = parent::addNestedConditions($callback, $joiner);

        // Add properties from conditions
        $this->selection = array_unique(
            array_merge($this->selection, $query->selection)
        );
    }

    protected function createSelection()
    {
        if (empty($this->selection)) {

            $selection = [];
            foreach ($this->entityReflection->getProperties() as $property) {

                // Exclude associations & computed properties
                if (!$property->hasOption(Reflection\Property::OPTION_ASSOC)
                    && !$property->hasOption(Reflection\Property::OPTION_COMPUTED)
                ) {
                    $selection[] = $property->getName(true);
                }
            }
        } else {

            // Include primary automatically if not provided
            if ($this->entityReflection->hasPrimary()) {

                $primaryName = $this->entityReflection
                    ->getPrimaryProperty()
                    ->getName();

                $selection = $this->selection;
                if (!in_array($primaryName, $selection)) {
                    $selection[] = $primaryName;
                }
            }

            // Unmap all names
            foreach ($selection as $index => $name) {
                $selection[$index] = $this->entityReflection->getProperty($name)->getName(true);
            }
        }

        // Add required keys from remote associations
        foreach ($this->associations["remote"] as $association) {

            if (($association instanceof Association\ManyToOne || $association instanceof Association\OneToOne)
                && !in_array($association->getReferencingKey(), $selection, true)
            ) {
                $selection[] = $association->getReferencingKey();
            }
        }

        return $selection;
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