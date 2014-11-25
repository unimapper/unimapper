<?php

namespace UniMapper\Association;

use UniMapper\Adapter,
    UniMapper\Reflection,
    UniMapper\Exception;

class OneToMany extends Multi
{

    protected $expression = "1:N\s*=\s*(.*)";

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (empty($this->matches[1])) {
            throw new Exception\DefinitionException(
                "You must define foreign key name '". $definition . "'!"
            );
        }
    }

    public function getForeignKey()
    {
        return $this->matches[1];
    }

    public function find(
        Adapter $currentAdapter,
        Adapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createSelect($this->getTargetResource());
        $query->setConditions(
            [
                [
                    $this->getForeignKey(),
                    "IN",
                    array_keys($primaryValues),
                    "AND"
                ]
            ]
        );

        $result = $targetAdapter->execute($query);

        if (!$result) {
            return [];
        }

        return $result;
    }

}