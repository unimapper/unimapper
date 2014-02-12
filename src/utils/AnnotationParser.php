<?php

namespace UniMapper\Utils;

/**
 * Utility class for parsing annotation
 */
class AnnotationParser
{

    /**
     * Get defined class properties in annotations
     *
     * @param string $class Class name
     *
     * @return array Collection of \UniMapper\Utils\Property
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public static function getEntityProperties($class)
    {
        $reflection = new \ReflectionClass($class);
        $classDoc = $reflection->getDocComment();
        preg_match_all(
            '#@property (.*?)\n#s',
            $classDoc,
            $annotations
        );
        $properties = array();
        foreach ($annotations[0] as $annotation) {
            $property = new Property($annotation, $reflection);
            if (isset($properties[$property->getName()])) {
                throw new \UniMapper\Exceptions\PropertyException(
                    "Duplicate property name $" . $property->getName(),
                    $reflection,
                    $annotation
                );
            }
            $properties[$property->getName()] = $property;
        }

        // Include inherited doc comments too
        if (stripos($classDoc, "{@inheritDoc}") !== false) {
            $properties = array_merge(
                $properties,
                $this->getEntityProperties($reflection->getParentClass()->name)
            );
        }

        return $properties;
    }

    /**
     * Get defined class mappers from annotations
     *
     * @param string $class Class name
     *
     * @return \UniMapper\Utils\Mapper
     *
     * @throws \UniMapper\Exceptions\PropertyException
     */
    public static function getEntityMappers($class)
    {
        $reflection = new \ReflectionClass($class);
        $classDoc = $reflection->getDocComment();
        preg_match_all(
            '#@mapper (.*?)\n#s',
            $classDoc,
            $annotations
        );
        $mappers = array();
        foreach ($annotations[0] as $annotation) {
            $mapper = new Mapper(
                trim(ltrim($annotation, "@mapper ")),
                $reflection
            );
            if (isset($mappers[$mapper->getClass()])) {
                throw new \UniMapper\Exceptions\PropertyException(
                    "Duplicate mapper definition!",
                    $reflection,
                    $annotation
                );
            }
            $mappers[$mapper->getClass()] = $mapper;
        }
        return $mappers;
    }

}