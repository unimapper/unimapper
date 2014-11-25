<?php

namespace UniMapper\Association;

use UniMapper\Adapter,
    UniMapper\Reflection,
    UniMapper\Exception;

class ManyToMany extends Multi
{

    protected $expression = "M(:|>|<)N=(.*)\|(.*)\|(.*)";

    protected $dominant = true;

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (!$targetReflection->hasPrimaryProperty()) {
            throw new Exception\DefinitionException(
                "Target entity must define primary property!"
            );
        }

        if ($this->isRemote() && $this->matches[1] === "<") {
            $this->dominant = false;
        }

        if (empty($this->matches[2])) {
            throw new Exception\DefinitionException(
                "You must define join key!"
            );
        }
        if (empty($this->matches[3])) {
            throw new Exception\DefinitionException(
                "You must define join resource!"
            );
        }

        if (empty($this->matches[4])) {
            throw new Exception\DefinitionException(
                "You must define reference key!!"
            );
        }
    }

    public function getJoinKey()
    {
        return $this->matches[2];
    }

    public function getJoinResource()
    {
        return $this->matches[3];
    }

    public function getReferenceKey()
    {
        return $this->matches[4];
    }

    public function getForeignKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

    public function isDominant()
    {
        return $this->dominant;
    }

    /**
     * @todo should be optimized with 1 query only on same adapters
     */
    public function find(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        if (!$this->isDominant()) {
            $currentAdapter = $targetAdapter;
        }

        $joinQuery = $currentAdapter->createSelect(
            $this->getJoinResource(),
            [$this->getJoinKey(), $this->getReferenceKey()]
        );
        $joinQuery->setConditions(
            [[$this->getJoinKey(), "IN", $primaryValues, "AND"]]
        );

        $joinResult = $currentAdapter->execute($joinQuery);

        if (!$joinResult) {
            return [];
        }

        $joinResult = $this->groupResult(
            $joinResult,
            [
                $this->getReferenceKey(),
                $this->getJoinKey()
            ]
        );

        $targetQuery = $targetAdapter->createSelect(
            $this->getTargetResource()
        );
        $targetQuery->setConditions(
            [
                [
                    $this->getForeignKey(),
                    "IN",
                    array_keys($joinResult),
                    "AND"
                ]
            ]
        );

        $targetResult = $targetAdapter->execute($targetQuery);
        if (!$targetResult) {
            return [];
        }

        $targetResult = $this->groupResult(
            $targetResult,
            [$this->getForeignKey()]
        );

        $result = [];
        foreach ($joinResult as $targetKey => $join) {

            foreach ($join as $originKey => $data) {
                if (!isset($targetResult[$targetKey])) {
                    throw new Exception\UnexpectedException(
                        "Can not merge associated result key '" . $targetKey
                        . "' not found in result from '"
                        . $this->getTargetResource()
                        . "'! Maybe wrong value in join table/resource."
                    );
                }
                $result[$originKey][] = $targetResult[$targetKey];
            }
        }

        return $result;
    }

    public function modify(
        $primaryValue,
        Adapter $sourceAdapter,
        Adapter $targetAdapter
    ) {
        if ($this->isRemote() && !$this->isDominant()) {
            $sourceAdapter = $targetAdapter;
        }

        $this->_executeModify($primaryValue, $sourceAdapter, $targetAdapter);
        $this->_executeModify(
            $primaryValue,
            $sourceAdapter,
            $targetAdapter,
            Adapter\IAdapter::ASSOC_REMOVE
        );
    }

    private function _executeModify(
        $primaryValue,
        Adapter $joinAdapter,
        Adapter $targetAdapter,
        $action = Adapter\IAdapter::ASSOC_ADD
    ) {
        if ($action === Adapter\IAdapter::ASSOC_REMOVE) {
            $assocKeys = $this->getDetached();
            $entities = $this->getRemoved();
        } else {
            $assocKeys = $this->getAttached();
            $entities = $this->getAdded();
        }

        foreach ($entities as $entity) {

            if ($action === Adapter\IAdapter::ASSOC_REMOVE) {

                $targetPrimaryName = $this->getTargetReflection()
                    ->getPrimaryProperty()
                    ->getName(true);
                $targetAdapter->execute(
                    $targetAdapter->createDeleteOne(
                        $this->getTargetResource(),
                        $targetPrimaryName,
                        $entity->{$targetPrimaryName}
                    )
                );
                $assocKeys[] = $entity->{$targetPrimaryName};
            } else {
                $assocKeys[] = $targetAdapter->execute(
                    $targetAdapter->createInsert($this->getTargetResource(), $entity->getData())
                );
            }
        }

        if ($assocKeys) {

            $adapterQuery = $joinAdapter->createModifyManyToMany(
                $this,
                $primaryValue,
                array_unique($assocKeys),
                $action
            );
            $joinAdapter->execute($adapterQuery);
        }
    }

}