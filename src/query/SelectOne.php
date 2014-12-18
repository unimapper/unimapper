<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Mapper,
    UniMapper\Modifier,
    UniMapper\Reflection;

class SelectOne extends Selectable
{

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        Mapper $mapper,
        $primaryValue
    ) {
        parent::__construct($entityReflection, $adapters, $mapper);

        if (!$entityReflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use query on entity without primary property!"
            );
        }

        $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);

        $this->primaryValue = $primaryValue;
    }

    protected function onExecute(\UniMapper\Adapter $adapter)
    {
        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createSelectOne(
            $this->entityReflection->getAdapterResource(),
            $primaryProperty->getName(true),
            $this->primaryValue
        );

        if ($this->associations["local"]) {
            $query->setAssociations($this->associations["local"]);
        }

        $result = $adapter->execute($query);

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

                if ($association->isCollection()) {
                    $modififer = new Modifier\CollectionModifier($association);
                } else {
                    $modififer = new Modifier\EntityModifier($association);
                }

                $associated = $modififer->load(
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

        return $this->mapper->mapEntity($this->entityReflection->getName(), $result);
    }

}