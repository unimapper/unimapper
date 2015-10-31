<?php

namespace UniMapper\Entity\Reflection\Property;

use UniMapper\Entity\Reflection\Property;

interface IOption
{

    /**
     * Create an option instance
     *
     * @param Property $property
     * @param null $value
     * @param array $parameters
     *
     * @return mixed
     */
    public static function create(
        Property $property,
        $value = null,
        array $parameters = []
    );

    /**
     * Called after all options on property created
     *
     * @param Property $property
     * @param mixed    $option
     */
    public static function afterCreate(Property $property, $option);

}