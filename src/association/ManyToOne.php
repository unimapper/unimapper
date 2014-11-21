<?php

namespace UniMapper\Association;

use UniMapper\Adapter,
    UniMapper\Reflection,
    UniMapper\Exception;

class ManyToOne extends Single
{

    protected $expression = "N:1\s*=\s*(.*)";

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (empty($this->matches[1])) {
            throw new Exception\PropertyDefinitionException(
                "You must define a reference key!"
            );
        }
    }

    public function getKey()
    {
        return $this->getReferenceKey();
    }

    public function getReferenceKey()
    {
        return $this->matches[1];
    }

    public function find(
        Adapter\IAdapter $currentAdapter,
        Adapter\IAdapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createFind($this->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->getTargetReflection()
                        ->getPrimaryProperty()
                        ->getName(true),
                    "IN",
                    $primaryValues,
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (empty($result)) {
            return [];
        }

        return $this->groupResult(
            $result,
            [
                $this->getTargetReflection()
                    ->getPrimaryProperty()
                    ->getName(true)
            ]
        );
    }

    public function modify(
        $primaryValue,
        Adapter\IAdapter $sourceAdapter,
        Adapter\IAdapter $targetAdapter
    ) {
        if ($this->getAttached()) {

            $adapterQuery = $sourceAdapter->createUpdateOne(
                $this->getSourceResource(),
                $this->getPrimaryKey(),
                $primaryValue,
                [$this->getReferenceKey() => $this->getAttached()]
            );
            $sourceAdapter->execute($adapterQuery);
        }
    }

}