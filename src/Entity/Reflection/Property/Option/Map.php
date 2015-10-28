<?php

namespace UniMapper\Entity\Reflection\Property\Option;

use UniMapper\Entity\Reflection;
use UniMapper\Exception\OptionException;

class Map implements Reflection\Property\IOption
{

    const KEY = "map";

    /** @var callable */
    private $filterIn;

    /** @var callable */
    private $filterOut;

    /** @var string */
    private $unmapped;

    public function __construct(
        Reflection\Property $property,
        $by = null,
        callable $filterIn = null,
        callable $filterOut = null
    ) {
        $this->unmapped = empty($by) ? $property->getName() : $by;
        $this->filterIn = $filterIn;
        $this->filterOut = $filterOut;
    }

    /**
     * Get unmapped name
     *
     * @return string
     */
    public function getUnmapped()
    {
        return $this->unmapped;
    }

    /**
     * @return callable
     */
    public function getFilterIn()
    {
        return $this->filterIn;
    }

    /**
     * @return callable
     */
    public function getFilterOut()
    {
        return $this->filterOut;
    }

    public static function create(
        Reflection\Property $property,
        $value = null,
        array $parameters = []
    ) {
        $filterIn = null;
        $filterOut = null;
        $by = null;

        // By
        if (array_key_exists(self::KEY . "-by", $parameters)) {
            $by = $parameters[self::KEY . "-by"];
        }

        // Filter
        if (array_key_exists(self::KEY . "-filter", $parameters)) {

            $filter = explode("|", $parameters[self::KEY . "-filter"]);

            if (!isset($filter[0]) || !isset($filter[1])) {
                throw new OptionException("You must define input/output filter!");
            }

            $class = $property->getEntityReflection()->getClassName();

            $filterIn = self::createCallback($class, $filter[0]);
            if (!$filterIn) {
                throw new OptionException("Invalid input filter definition!");
            }

            $filterOut = self::createCallback($class, $filter[1]);
            if (!$filterOut) {
                throw new OptionException("Invalid output filter definition!");
            }
        }

        return new self($property, $by, $filterIn, $filterOut);
    }

    private static function createCallback($class, $method)
    {
        if (method_exists($class, $method)) {
            return [$class, $method];
        } elseif (is_callable($method)) {
            return $method;
        }

        return false;
    }

}