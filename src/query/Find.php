<?php

namespace UniMapper\Query;

use UniMapper\Exception\QueryException,
    UniMapper\Reflection,
    UniMapper\Reflection\Entity\Property\Association\BelongsToMany,
    UniMapper\Reflection\Entity\Property\Association\HasMany,
    UniMapper\EntityCollection,
    UniMapper\Adapter,
    UniMapper\Query\IConditionable;

class Find extends \UniMapper\Query implements IConditionable
{

    protected $limit;
    protected $offset;
    protected $orderBy = [];
    protected $selection = [];

    /** @var array */
    private $associations = [
        "local" => [],
        "remote" => []
    ];

    public function __construct(Reflection\Entity $entityReflection, array $adapters)
    {
        parent::__construct($entityReflection, $adapters);
        $this->selection = array_slice(func_get_args(), 2);
    }

    public function select($name)
    {
        if (!array_search($name, $this->selection)) {
            $this->selection[] = $name;
        }
    }

    public function associate($propertyName)
    {
        foreach (func_get_args() as $name) {

            if (!isset($this->entityReflection->getProperties()[$name])) {
                throw new QueryException("Property '" . $name . "' not defined!");
            }

            $property = $this->entityReflection->getProperties()[$name];
            if (!$property->isAssociation()) {
                throw new QueryException(
                    "Property '" . $name . "' is not defined as association!"
                );
            }

            $association = $property->getAssociation();
            if ($association->isRemote()) {
                $this->associations["remote"][$name] = $association;
            } else {
                $this->associations["local"][$name] = $association;
            }
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

    public function orderBy($propertyName, $direction = "asc")
    {
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new QueryException(
                "Invalid property name '" . $propertyName . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== "asc" && $direction !== "desc") {
            throw new QueryException("Order direction must be 'asc' or 'desc'!");
        }
        $this->orderBy[$propertyName] = $direction;
        return $this;
    }

    public function onExecute(Adapter $adapter)
    {
        $mapping = $adapter->getMapping();

        $result = $adapter->find(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $mapping->unmapSelection($this->entityReflection, $this->selection),
            $mapping->unmapConditions($this->entityReflection, $this->conditions),
            $mapping->unmapOrderBy($this->entityReflection, $this->orderBy),
            $this->limit,
            $this->offset,
            $this->associations["local"]
        );
        if (empty($result)) {
            return new EntityCollection($this->entityReflection->getClassName());
        }

        if ($this->associations["remote"]) {

            // Get remote associations
            $primaryPropertyName = $this->entityReflection->getPrimaryProperty()
                ->getMappedName();

            $primaryValues = [];
            foreach ($result as $item) {

                if (!is_array($item)) {
                    $item = (array) $item;
                }
                $primaryValues[] = $item[$primaryPropertyName];
            }

            $associated = [];
            foreach ($this->associations["remote"]
                as $propertyName => $association
            ) {

                if ($association instanceof HasMany) {
                    $associated[$propertyName] = $this->_hasMany(
                        $adapter,
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        $primaryValues
                    );
                } elseif ($association instanceof HasOne) {
                    $associated[$propertyName] = $this->_belongsToMany(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        $primaryValues
                    );
                } else {
                    throw new QueryException(
                        "Unsupported remote association "
                        . get_class($association) . "!"
                    );
                }
            }

            // Merge returned associations
            foreach ($result as $index => $item) {

                foreach ($associated as $propertyName => $associatedResult) {

                    // @todo potencial future bug, association wrong?
                    $primaryValue = $item->{$association->getPrimaryKey()};

                    if (isset($associatedResult[$primaryValue])) {
                        $item[$propertyName] = $associatedResult[$primaryValue];
                    }
                }
            }
        }

        return $mapping->mapCollection(
            $this->entityReflection->getClassName(),
            $result
        );
    }

    protected function addCondition($propertyName, $operator, $value,
        $joiner = 'AND'
    ) {
        parent::addCondition($propertyName, $operator, $value, $joiner);

        // Add properties from conditions
        if (count($this->selection) > 0
            && !in_array($propertyName, $this->selection)
        ) {
            $this->selection[] = $propertyName;
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

    private function _hasMany(Adapter $currentAdapter, Adapter $targetAdapter,
        HasMany $association, array $primaryValues
    ) {
        $joinResult = $currentAdapter->find(
            $association->getJoinResource(),
            [$association->getJoinKey(), $association->getReferenceKey()],
            [[$association->getJoinKey(), "IN", $primaryValues, "AND"]]
        );

        $joinResult = $this->_groupResult(
            $joinResult,
            [$association->getReferenceKey(),
            $association->getJoinKey()]
        );

        $targetResult = $targetAdapter->find(
            $association->getTargetResource(),
            [], // @todo
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($joinResult),
                    "AND"
                ]
            ]
        );

        $targetResult = $this->_groupResult(
            $targetResult,
            [$association->getForeignKey()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new \Exception(
                        "Can not merge associated result key '" . $targetKey
                        . "' not found in result from '"
                        . $association->getTargetResource()
                        . "'! Maybe wrong value in join table/resource."
                    );
                }
                $result[$originKey][] = $targetResult[$targetKey];
            }
        }

        return $result;
    }

    private function _belongsToMany(Adapter $targetAdapter,
        BelongsToMany $association, array $primaryValues
    ) {
        $result = $targetAdapter->find(
            $association->getTargetResource(),
            [], // @todo
            [
                [
                    $association->getForeignKey(),
                    "IN",
                    array_keys($primaryValues), "AND"
                ]
            ]
        );

        if (!$result) {
            return [];
        }

        return $result;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->offset;
    }

    public function getOrderBy()
    {
        return $this->orderBy;
    }

    public function getSelection()
    {
        return $this->selection;
    }

    /**
     * Group associative array
     *
     * @param array $original
     * @param array $keys
     * @param int   $level
     *
     * @return array
     *
     * @link http://tigrou.nl/2012/11/26/group-a-php-array-to-a-tree-structure/
     */
    private function _groupResult($original, $keys, $level = 0)
    {
        $converted = [];
        $key = $keys[$level];
        $isDeepest = sizeof($keys) - 1 == $level;

        $level++;

        $filtered = [];
        foreach ($original as $k => $subArray) {

            $subArray = (array) $subArray;
            $thisLevel = $subArray[$key];
            if ($isDeepest) {
                $converted[$thisLevel] = $subArray;
            } else {
                $converted[$thisLevel] = [];
            }
            $filtered[$thisLevel][] = $subArray;
        }

        if (!$isDeepest) {
            foreach (array_keys($converted) as $value) {
                $converted[$value] = $this->_groupResult(
                    $filtered[$value],
                    $keys,
                    $level
                );
            }
        }

        return $converted;
    }

}
