<?php

namespace UniMapper\Query;

use UniMapper\Mapper,
    UniMapper\Reflection;

class Insert extends \UniMapper\Query
{

    /** @var \UniMapper\Entity */
    public $entity;

    /** @var boolean */
    public $returnPrimaryValue = true;

    public function __construct(Reflection\Entity $entityReflection, Mapper $mapper, array $data)
    {
        parent::__construct($entityReflection, $mapper);
        $class = $this->entityReflection->getClassName();
        $this->entity = new $class; // @todo missing cache
        $this->entity->import($data); // @todo better validation?
    }

    public function onExecute(\UniMapper\Mapper $mapper)
    {
        $result = $mapper->insert($this);

        $primaryProperty = $this->entityReflection->getPrimaryProperty();
        if ($primaryProperty !== null) {
            $this->entity->{$primaryProperty->getName()} = $result;
        }

        return $this->entity;
    }

}