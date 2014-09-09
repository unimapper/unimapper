<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection,
    UniMapper\Reflection\Entity\Property\Association\BelongsToMany,
    UniMapper\Reflection\Entity\Property\Association\HasMany,
    UniMapper\EntityCollection;

class Find extends Selection implements IConditionable
{

    protected $limit;
    protected $offset;
    protected $orderBy = [];
    protected $selection = [];

    public function __construct(Reflection\Entity $entityReflection, array $adapters)
    {
        parent::__construct($entityReflection, $adapters);

        $selection = array_slice(func_get_args(), 2);
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
        if ($property->isAssociation() || $property->isComputed()) {
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

    public function orderBy($propertyName, $direction = "asc")
    {
        if (!$this->entityReflection->hasProperty($propertyName)) {
            throw new Exception\QueryException(
                "Invalid property name '" . $propertyName . "'!"
            );
        }

        $direction = strtolower($direction);
        if ($direction !== "asc" && $direction !== "desc") {
            throw new Exception\QueryException("Order direction must be 'asc' or 'desc'!");
        }
        $this->orderBy[$propertyName] = $direction;
        return $this;
    }

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        $mapping = $adapter->getMapping();

        $result = $adapter->find(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $mapping->unmapSelection($this->_createSelection(), $this->entityReflection),
            $mapping->unmapConditions($this->conditions, $this->entityReflection),
            $mapping->unmapOrderBy($this->orderBy, $this->entityReflection),
            $this->limit,
            $this->offset,
            $this->associations["local"]
        );

        if (empty($result)) {
            return new EntityCollection($this->entityReflection);
        }

        // Get remote associations
        if ($this->associations["remote"]) {

            $primaryPropertyName = $this->entityReflection->getPrimaryProperty()
                ->getMappedName();

            $primaryValues = [];
            foreach ($result as $item) {

                if (!is_array($item)) {
                    $item = (array) $item;
                }
                $primaryValues[] = $item[$primaryPropertyName];
            }

            foreach ($this->associations["remote"]
                as $propertyName => $association
            ) {

                if (!isset($this->adapters[$association->getTargetAdapterName()])) {
                    throw new Exception\QueryException(
                        "Adapter with name '"
                        . $association->getTargetAdapterName() . "' not set!"
                    );
                }

                if ($association instanceof HasMany) {
                    $associated = $this->hasMany(
                        $adapter,
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        $primaryValues
                    );
                } elseif ($association instanceof BelongsToMany) {
                    $associated = $this->belongsToMany(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        $primaryValues
                    );
                } else {
                    throw new Exception\QueryException(
                        "Unsupported remote association "
                        . get_class($association) . "!"
                    );
                }

                if (!$associated) {
                    continue;
                }

                // Merge returned associations
                foreach ($result as $index => $item) {

                    $primaryValue = $item[$association->getPrimaryKey()];
                    if (isset($associated[$primaryValue])) {
                        $result[$index][$propertyName] = $associated[$primaryValue];
                    }
                }
            }
        }

        return $mapping->mapCollection($this->entityReflection, $result);
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

    private function _createSelection()
    {
        if (empty($this->selection)) {

            $selection = [];
            foreach ($this->entityReflection->getProperties() as $property) {

                if (!$property->isAssociation() && !$property->isComputed()) {
                    $selection[] = $property->getName();
                }
            }
        } else {
            $primaryPropertyName = $this->entityReflection
                ->getPrimaryProperty()
                ->getName();

            // Add primary property automatically
            $selection = $this->selection;
            if (!in_array($primaryPropertyName, $selection)) {
                $selection[] = $primaryPropertyName;
            }
        }

        return $selection;
    }

}