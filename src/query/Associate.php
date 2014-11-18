<?php

namespace UniMapper\Query;

use UniMapper\Association,
    UniMapper\Reflection,
    UniMapper\Exception,
    UniMapper\Adapter;

class Associate extends \UniMapper\Query
{

    /** @var mixed */
    protected $primaryValue;

    /** @var Association */
    protected $association;

    public function __construct(
        Reflection\Entity $entityReflection,
        array $adapters,
        $primaryValue,
        Association $association
    ) {
        parent::__construct($entityReflection, $adapters);
        if (empty($primaryValue)) {
            throw new Exception\QueryException("Primary value can not be empty!");
        }
        $this->primaryValue = $primaryValue;
        $this->association = $association;
    }

    protected function onExecute(Adapter\IAdapter $adapter)
    {
        if ($this->association instanceof Association\ManyToMany) {
            // M:N

            if ($this->association->isRemote() && !$this->association->isDominant()) {
                $adapter = $this->adapters[$this->association->getTargetAdapterName()];
            }

            $this->_manyToMany($adapter);
            $this->_manyToMany($adapter, Adapter\IAdapter::ASSOC_REMOVE);
        } elseif ($this->association instanceof Association\ManyToOne) {
            // N:1

            $this->_manyToOne();
        } else {
            throw new Exception\QueryException(
                "Modifications on association " . get_class($this->association) . " is not supported!"
            );
        }
    }

    private function _manyToMany(
        Adapter\IAdapter $adapter,
        $action = Adapter\IAdapter::ASSOC_ADD
    ) {
        if ($action === Adapter\IAdapter::ASSOC_REMOVE) {
            $refKeys = $this->association->getDetached();
            $entities = $this->association->getRemoved();
        } else {
            $refKeys = $this->association->getAttached();
            $entities = $this->association->getAdded();
        }

        foreach ($entities as $entity) {

            if ($action === Adapter\IAdapter::ASSOC_REMOVE) {

                $primaryName = $entity->getReflection()->getPrimaryProperty()->getName();
                $query = new Delete($entity->getReflection(), $this->adapters);
                $query->where($primaryName, "=", $entity->{$primaryName})->execute();
                $refKeys[] = $entity->{$primaryName};
            } else {

                $query = new Insert($entity->getReflection(), $this->adapters, $entity->getData());
                $refKeys[] = $query->execute();
            }

            $this->adapterQueries += $query->adapterQueries;
        }

        if ($refKeys) {

            $adapterQuery = $adapter->createModifyManyToMany(
                $this->association,
                $this->primaryValue,
                array_unique($refKeys),
                $action
            );
            $adapter->execute($adapterQuery);

            $this->adapterQueries[] = $adapterQuery->getRaw();
        }
    }

    private function _manyToOne()
    {
        $adapter = $this->adapters[$this->entityReflection->getAdapterReflection()->getName()];

        if ($this->association->getAttached()) {

            $adapterQuery = $adapter->createUpdateOne(
                $this->entityReflection->getAdapterReflection()->getResource(),
                $this->entityReflection->getPrimaryProperty()->getName(true),
                $this->primaryValue,
                [$this->association->getReferenceKey() => $this->association->getAttached()]
            );
            $adapter->execute($adapterQuery);

            $this->adapterQueries[] = $adapterQuery->getRaw();
        }
    }

}