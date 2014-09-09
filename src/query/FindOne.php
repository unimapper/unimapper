<?php

namespace UniMapper\Query;

use UniMapper\Exception,
    UniMapper\Reflection\Entity\Property\Association\HasMany,
    UniMapper\Reflection\Entity\Property\Association\BelongsToMany,
    UniMapper\Reflection;

class FindOne extends Selection
{

    /** @var mixed */
    public $primaryValue;

    public function __construct(Reflection\Entity $entityReflection,
        array $adapters, $primaryValue
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

            $result = (array) $result;

            $primaryValue = $result[$primaryProperty->getMappedName()];

            foreach ($this->associations["remote"]
                as $propertyName => $association
            ) {

                if (!isset($this->adapters[$association->getTargetAdapterName()])) {
                    throw new Exception\QueryException(
                        "Adapter with name '"
                        . $association->getTargetAdapterName() . "' not set!"
                    );
                }

                if ($association instanceof HasMany) {
                    $associated = $this->hasMany(
                        $adapter,
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$primaryValue]
                    );
                } elseif ($association instanceof BelongsToMany) {
                    $associated = $this->belongsToMany(
                        $this->adapters[$association->getTargetAdapterName()],
                        $association,
                        [$primaryValue]
                    );
                } else {
                    throw new Exception\QueryException(
                        "Unsupported remote association "
                        . get_class($association) . "!"
                    );
                }

                // Merge returned associations
                if (isset($associated[$primaryValue])) {
                    $result[$propertyName] = $associated[$primaryValue];
                }
            }
        }

        return $adapter->getMapping()->mapEntity(
            $this->entityReflection,
            $result
        );
    }

}