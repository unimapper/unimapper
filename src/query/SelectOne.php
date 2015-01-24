<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection;

class SelectOne extends \UniMapper\Query
{

    use Selectable;

    /** @var mixed */
    protected $primaryValue;

    public function __construct(
        Reflection\Entity $entityReflection,
        $primaryValue
    ) {
        parent::__construct($entityReflection);

        // Primary
        if (!$entityReflection->hasPrimary()) {
            throw new Exception\QueryException(
                "Can not use query on entity without primary property!"
            );
        }
        $entityReflection->getPrimaryProperty()->validateValueType($primaryValue);
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