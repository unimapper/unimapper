<?php

namespace UniMapper\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class ManyToMany extends Multi
{

    protected $expression = "M(:|>|<)N=(.*)\|(.*)\|(.*)";

    protected $dominant = true;

    public function __construct(
        Reflection\Entity\Property $propertyReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($propertyReflection, $targetReflection, $definition);

        if (!$targetReflection->hasPrimaryProperty()) {
            throw new Exception\DefinitionException(
                "Target entity must define primary property!"
            );
        }

        if ($this->isRemote() && $this->matches[1] === "<") {
            $this->dominant = false;
        }

        if (empty($this->matches[2])) {
            throw new Exception\DefinitionException(
                "You must define join key!"
            );
        }
        if (empty($this->matches[3])) {
            throw new Exception\DefinitionException(
                "You must define join resource!"
            );
        }

        if (empty($this->matches[4])) {
            throw new Exception\DefinitionException(
                "You must define reference key!!"
            );
        }
    }

    public function getJoinKey()
    {
        return $this->matches[2];
    }

    public function getJoinResource()
    {
        return $this->matches[3];
    }

    public function getReferenceKey()
    {
        return $this->matches[4];
    }

    public function getForeignKey()
    {
        return $this->targetReflection->getPrimaryProperty()->getName(true);
    }

    public function isDominant()
    {
        return $this->dominant;
    }

}