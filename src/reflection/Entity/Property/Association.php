<?php

namespace UniMapper\Reflection\Entity\Property;

use UniMapper\Reflection,
    UniMapper\Exception;

abstract class Association
{

    /** @var string $expression Regular expression for definition */
    protected $expression;

    /** @var array $matches Matched items from regular */
    protected $matches;

    /** @var \UniMapper\Reflection\Entity */
    protected $currentReflection;

    /** @var \UniMapper\Reflection\Entity */
    protected $targetReflection;

    public function __construct(
        Reflection\Entity $currentReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        if (!preg_match("/" . $this->expression . "/", $definition, $matches)) {
            throw new Exception\AssociationParseException(
                "Invalid association type definition '". $definition . "'!",
                Exception\AssociationParseException::INVALID_TYPE
            );
        }
        $this->matches = $matches;

        $this->currentReflection = $currentReflection;
        $this->targetReflection = $targetReflection;
    }

    public function getPrimaryKey()
    {
        return $this->currentReflection->getPrimaryProperty()->getMappedName();
    }

    public function getTargetReflection()
    {
        return $this->targetReflection;
    }

    public function getTargetResource()
    {
        return $this->targetReflection->getAdapterReflection()->getResource();
    }

    public function getTargetAdapterName()
    {
        return $this->targetReflection->getAdapterReflection()->getName();
    }

    public function isRemote()
    {
        return $this->currentReflection->getAdapterReflection()->getName()
            !== $this->targetReflection->getAdapterReflection()->getName();
    }

}