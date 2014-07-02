<?php

namespace UniMapper\Query;

use UniMapper\Exceptions\QueryException,
    UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    private $entity;

    public function __construct(Reflection\Entity $entityReflection, array $mappers, array $data)
    {
        parent::__construct($entityReflection, $mappers);

        $this->entity = $entityReflection->createEntity();
        $this->entity->import($data); // @todo easier validation
    }

    public function getValues()
    {
        return $this->entity->getData();
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $values = $mapper->unmapEntity($this->entity);

        // Values can not be empty
        if (empty($values)) {
            throw new QueryException("Nothing to update!");
        }

        // Prevent to set empty primary property
        if ($this->entityReflection->hasPrimaryProperty()) {

            $primaryName = $this->entityReflection->getPrimaryProperty()->getMappedName();
            if (empty($values[$primaryName])) {
                unset($values[$primaryName]);
            }
        }

        $primaryValue = $mapper->insert(
            $this->entityReflection->getMapperReflection()->getResource(),
            $values
        );

        if ($this->entityReflection->hasPrimaryProperty()) {

            if ($primaryValue === null) {
                throw new QueryException("Insert should return primary value but null given!");
            }
            return $mapper->mapValue($this->entityReflection->getPrimaryProperty(), $primaryValue);
        }
    }

}