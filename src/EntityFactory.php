<?php

namespace UniMapper;

class EntityFactory
{

    /**
     * Create entity
     *
     * @param string $name
     * @param mixed  $values Iterable value like array or stdClass object
     *
     * @return Entity
     */
    public function createEntity($name, $values = null)
    {
        return Reflection\Loader::load($name)->createEntity($values);
    }

    /**
     * Create entity collection
     *
     * @param string $name
     * @param mixed  $values Iterable value like array or stdClass object
     *
     * @return EntityCollection
     */
    public function createCollection($name, $values = null)
    {
        // Create empty collection
        $collection = new EntityCollection($name);

        // Add values
        if ($values) {

            $class = $collection->getEntityReflection()->getClassName();
            foreach ($values as $item) {

                if (!$item instanceof $class) {
                    $item = $this->createEntity($name, $item);
                }
                $collection[] = $item;
            }
        }

        return $collection;
    }

}