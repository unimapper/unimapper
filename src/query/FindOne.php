<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Association\ManyToMany,
    UniMapper\Association\ManyToOne,
    UniMapper\Association\OneToMany,
    UniMapper\Association\OneToOne,
    UniMapper\Reflection;

class FindOne extends Selectable
{

    /** @var mixed */
    protected $primaryValue;

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

    protected function onExecute(\UniMapper\Adapter\IAdapter $adapter)
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createFindOne(
            $this->entityReflection->getAdapterReflection()->getResource(),
            $primaryProperty->getName(true),
            $this->primaryValue
        );

        if ($this->associations["local"]) {
            $query->setAssociations($this->associations["local"]);
        }

        $result = $adapter->execute($query);

        $this->adapterQueries[] = $query->getRaw();

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

                $refValue = $result[$primaryProperty->getName(true)];

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

        return $adapter->getMapper()->mapEntity(
            $this->entityReflection,
            $result
        );
    }

}