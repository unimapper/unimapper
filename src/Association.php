<?php

namespace UniMapper;

use UniMapper\Association\ManyToMany;
use UniMapper\Association\ManyToOne;
use UniMapper\Association\OneToMany;
use UniMapper\Association\OneToOne;
use UniMapper\Entity\Reflection;
use UniMapper\Entity\Reflection\Property\Option\Assoc;
use UniMapper\Exception\AssociationException;

class Association
{

    const JOINER = "_";

    /** @var Reflection */
    protected $sourceReflection;

    /** @var Reflection */
    protected $targetReflection;

    /**
     * @param Reflection $sourceReflection
     * @param Reflection $targetReflection
     *
     * @throws AssociationException
     */
    public function __construct(
        Reflection $sourceReflection,
        Reflection $targetReflection
    ) {
        if ($sourceReflection->getAdapterName() === $targetReflection->getAdapterName()) {
            throw new AssociationException(
                "Associations with same adapters should be managed by relevant adapter!"
            );
        }

        $this->sourceReflection = $sourceReflection;
        $this->targetReflection = $targetReflection;
    }

    /**
     * Key name that refers target results to source entity
     *
     * @return type
     */
    public function getKey()
    {
        return $this->sourceReflection->getPrimaryProperty()->getUnmapped();
    }

    /**
     * @param Assoc $option
     *
     * @return Association
     *
     * @throws AssociationException
     */
    public static function create(Assoc $option)
    {
        $definition = $option->getDefinition();

        switch ($option->getType()) {
            case "m:n":
            case "m>n":
            case "m<n":
                return new ManyToMany(
                    $option->getSourceReflection(),
                    $option->getTargetReflection(),
                    $definition,
                    $option->getType() === "m<n" ? false : true
                );
            case "1:1":
                return new OneToOne(
                    $option->getSourceReflection(),
                    $option->getTargetReflection(),
                    isset($definition[0]) ? $definition[0] : null
                );
            case "1:n":
                return new OneToMany(
                    $option->getSourceReflection(),
                    $option->getTargetReflection(),
                    isset($definition[0]) ? $definition[0] : null
                );
            case "n:1":
                return new ManyToOne(
                    $option->getSourceReflection(),
                    $option->getTargetReflection(),
                    isset($definition[0]) ? $definition[0] : null
                );
            default:
                throw new AssociationException("Unsupported association type");
        }
    }

    /**
     * Group associative array
     *
     * @param array $original
     * @param array $keys
     * @param int   $level
     *
     * @return array
     *
     * @link http://tigrou.nl/2012/11/26/group-a-php-array-to-a-tree-structure/
     *
     * @throws \Exception
     */
    public static function groupResult(array $original, array $keys, $level = 0)
    {
        $converted = [];
        $key = $keys[$level];
        $isDeepest = sizeof($keys) - 1 == $level;

        $level++;

        $filtered = [];
        foreach ($original as $k => $subArray) {

            $subArray = (array) $subArray;
            if (!isset($subArray[$key])) {
                throw new AssociationException(
                    "Index '" . $key . "' not found on level '" . $level . "'!"
                );
            }

            $thisLevel = $subArray[$key];

            if (is_object($thisLevel)) {
                $thisLevel = (string) $thisLevel;
            }

            if ($isDeepest) {
                $converted[$thisLevel] = $subArray;
            } else {
                $converted[$thisLevel] = [];
            }
            $filtered[$thisLevel][] = $subArray;
        }

        if (!$isDeepest) {
            foreach (array_keys($converted) as $value) {
                $converted[$value] = self::groupResult(
                    $filtered[$value],
                    $keys,
                    $level
                );
            }
        }

        return $converted;
    }

}