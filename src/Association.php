<?php

namespace UniMapper;

abstract class Association
{

    /** @var string $expression Regular expression for definition */
    protected $expression;

    /** @var array $matches Matched items from regular */
    protected $matches;

    /** @var \UniMapper\Reflection\Entity\Property */
    protected $propertyReflection;

    /** @var \UniMapper\Reflection\Entity */
    protected $targetReflection;

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        if (!preg_match("/" . $this->expression . "/", $definition, $matches)) {
            throw new Exception\DefinitionException(
                "Invalid association type definition '". $definition . "'!",
                Exception\DefinitionException::DO_NOT_FAIL
            );
        }
        $this->matches = $matches;

        $this->propertyReflection = $propertyReflection;
        $this->targetReflection = $targetReflection;
    }

    public function getPrimaryKey()
    {
        return $this->propertyReflection->getEntityReflection()
            ->getPrimaryProperty()
            ->getMappedName();
    }

    public function getTargetReflection()
    {
        return $this->targetReflection;
    }

    public function getTargetResource()
    {
        return $this->targetReflection->getAdapterReflection()->getResource();
    }

    public function getSourceResource()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterReflection()->getResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterReflection()->getName();
    }

    public function isRemote()
    {
        return $this->propertyReflection->getEntityReflection()->getAdapterReflection()->getName()
            !== $this->targetReflection->getAdapterReflection()->getName();
    }

    public function getPropertyName()
    {
        return $this->propertyReflection->getName();
    }

    protected function validateEntity(Entity $entity, $requirePrimary = false)
    {
        $class = $this->getTargetReflection()->getClassName();

        if (!$entity instanceof $class) {
            throw new Exception\AssociationException(
                "You can associate only " . $class . " entity!"
            );
        }

        if ($requirePrimary) {

            $primaryName = $entity->getReflection()->getPrimaryProperty()->getName();
            if (empty($entity->{$primaryName})) {
                throw new Exception\AssociationException(
                    "Primary value is required in association modifications!"
                );
            }
        }
    }
}