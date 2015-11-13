<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Entity\Reflection;

class SelectOne extends \UniMapper\Query
{

    use Selectable;

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection $entityReflection,
        $primaryValue
    ) {
        parent::__construct($entityReflection);

        // Primary
        if (!$entityReflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use query on entity without primary property!"
            );
        }

        try {
            $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);
        } catch (Exception\InvalidArgumentException $e) {
            throw new Exception\QueryException($e->getMessage());
        }

        $this->primaryValue = $primaryValue;

        // Selection
        $this->select(array_slice(func_get_args(), 3));
    }

    protected function onExecute(\UniMapper\Connection $connection)
    {
        $adapter = $connection->getAdapter($this->entityReflection->getAdapterName());

        $primaryProperty = $this->entityReflection->getPrimaryProperty();

        $query = $adapter->createSelectOne(
            $this->entityReflection->getAdapterResource(),
            $primaryProperty->getUnmapped(),
            $connection->getMapper()->unmapValue(
                $primaryProperty,
                $this->primaryValue
            )
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

                $assocValue = $result[$association->getKey()];

                $associated = $association->load($connection, [$assocValue]);

                // Merge returned associations
                if (isset($associated[$assocValue])) {
                    $result[$colName] = $associated[$assocValue];
                }
            }
        }

        return $connection->getMapper()->mapEntity($this->entityReflection->getName(), $result);
    }

}