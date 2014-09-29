<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection\Entity\Property\Association\ManyToMany,
    UniMapper\Reflection\Entity\Property\Association\ManyToOne,
    UniMapper\Reflection\Entity\Property\Association\OneToMany,
    UniMapper\Reflection\Entity\Property\Association\OneToOne,
    UniMapper\Reflection;

class FindOne extends Selection
{

    /** @var mixed */
    public $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        $primaryValue
    ) {
        parent::__construct($entityReflection, $adapters);

        if (!$entityReflection->hasPrimaryProperty()) {
            throw new Exception\QueryException(
                "Can not use findOne() on entity without primary property!"
            );
        }

        $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);

        $this->primaryValue = $primaryValue;
    }

    public function onExecute(\UniMapper\Adapter $adapter)
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $result = $adapter->findOne(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $primaryProperty->getMappedName(),
            $this->primaryValue,
            $this->associations["local"]
        );

        if (!$result) {
            return false;
        }

        // Get remote associations
        if ($this->associations["remote"]) {

            settype($result, "array");

            foreach ($this->associations["remote"] as $colName => $association) {

                if (!isset($this->adapters[$association->getTargetAdapterName()])) {
                    throw new Exception\QueryException(
                        "Adapter with name '"
                        . $association->getTargetAdapterName() . "' not set!"
                    );
                }

                $refValue = $result[$primaryProperty->getMappedName()];

                if ($association instanceof ManyToMany) {

                    $associated = $this->manyToMany(
                        $adapter,
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$refValue]
                    );
                } elseif ($association instanceof OneToOne) {

                    $refValue = $result[$association->getForeignKey()];

                    $associated = $this->oneToOne(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$refValue]
                    );
                } elseif ($association instanceof ManyToOne) {

                    $refValue = $result[$association->getReferenceKey()];

                    $associated = $this->manyToOne(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$refValue]
                    );
                } elseif ($association instanceof OneToMany) {

                    $associated = $this->oneToMany(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$refValue]
                    );
                } else {

                    throw new Exception\QueryException(
                        "Unsupported remote association "
                        . get_class($association) . "!"
                    );
                }

                // Merge returned associations
                if (isset($associated[$refValue])) {
                    $result[$colName] = $associated[$refValue];
                }
            }
        }

        return $adapter->getMapping()->mapEntity(
            $this->entityReflection,
            $result
        );
    }

}