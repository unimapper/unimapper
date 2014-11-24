<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class SelectOne extends Selectable
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
                "Can not use query on entity without primary property!"
            );
        }

        $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);

        $this->primaryValue = $primaryValue;
    }

    protected function onExecute(\UniMapper\Adapter\IAdapter $adapter)
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createSelectOne(
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

                $assocValue = $result[$association->getKey()];

                $associated = $association->find(
                    $adapter,
                    $this->adapters[$association->getTargetAdapterName()],
                    [$assocValue]
                );

                // Merge returned associations
                if (isset($associated[$assocValue])) {
                    $result[$colName] = $associated[$assocValue];
                }
            }
        }

        return $adapter->getMapper()->mapEntity(
            $this->entityReflection,
            $result
        );
    }

}