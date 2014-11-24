<?php

namespace UniMapper\Association;

use UniMapper\Adapter;

class OneToOne extends Single
{

    protected $expression = "1:1\s*=\s*(.*)";

    public function getKey()
    {
        return $this->getForeignKey();
    }

    public function find(
        Adapter\IAdapter $currentAdapter,
        Adapter\IAdapter $targetAdapter,
        array $primaryValues
    ) {
        $query = $targetAdapter->createSelect($this->getTargetResource());
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

}