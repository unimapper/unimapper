<?php

namespace UniMapper\Reflection\Entity\Property\Association;

use UniMapper\Reflection,
    UniMapper\Exception;

class HasMany extends Reflection\Entity\Property\Association
{

    protected $expression = "M(:|>|<)N=(.*)\|(.*)\|(.*)";

    protected $dominant = true;

    public function __construct(
        Reflection\Entity $currentReflection,
        Reflection\Entity $targetReflection,
        $definition
    ) {
        parent::__construct($currentReflection, $targetReflection, $definition);

        if (!$targetReflection->hasPrimaryProperty()) {
            throw new Exception\AssociationParseException(
                "Target entity must define primary property!"
            );
        }

        if ($this->isRemote() && $this->matches[1] === "<") {
            $this->dominant = false;
        }

        if (empty($this->matches[2])) {
            throw new Exception\AssociationParseException(
                "You must define join key!"
            );
        }
        if (empty($this->matches[3])) {
            throw new Exception\AssociationParseException(
                "You must define join resource!"
            );
        }

        if (empty($this->matches[4])) {
            throw new Exception\AssociationParseException(
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
        return $this->targetReflection->getPrimaryProperty()->getMappedName();
    }

    public function isDominant()
    {
        return $this->dominant;
    }

}