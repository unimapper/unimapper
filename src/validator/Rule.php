<?php

namespace UniMapper\Validator;

use UniMapper\Reflection,
    UniMapper\EntityCollection,
    UniMapper\Entity;

class Rule
{

    const ERROR = 1,
          WARNING = 2,
          INFO = 3,
          DEBUG = 4;

    protected $entity;
    protected $property;
    protected $validation;
    protected $message;
    protected $severity;
    protected $child;
    protected $childFailed = [];

    protected $path;

    public function __construct(
        Entity $entity,
        callable $validation,
        $message,
        Reflection\Property $property = null,
        $severity = self::ERROR,
        $child = null
    ) {
        $this->entity = $entity;
        $this->validation = $validation;
        $this->message = $message;
        $this->property = $property;
        $this->severity = $severity;
        $this->child = $child;
    }

    /**
     * @param array $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function getPath()
    {
        if ($this->path === null) {

            $this->path = [];
            if ($this->property) {

                $this->path[] = $this->property->getName();
                if ($this->child) {
                    $this->path[] = $this->child;
                }
            }
        }
        return $this->path;
    }



    public function getProperty()
    {
        return $this->property;
    }

    public function getSeverity()
    {
        return $this->severity;
    }

    public function getChild()
    {
        return $this->child;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFailedChildIndexes()
    {
        return $this->childFailed;
    }

    public function setChildFailed(array $indexes = [])
    {
        $this->childFailed = $indexes;
    }

    public function validate($failOn = self::ERROR)
    {
        $definition = $this->validation;
        if ($this->property) {

            $value = $this->entity->{$this->property->getName()};
            if ($this->child) {

                $this->childFailed = [];
                if ($value instanceof EntityCollection) {

                    foreach ($value as $index => $entity) {

                        if (!$definition($entity->{$this->child}, $entity, $index)) {
                            $this->childFailed[] = $index;
                        }
                    }
                    unset($entity);
                    return count($this->childFailed) === 0;
                } else {
                    return (bool) $definition($value->{$this->child});
                }
            } else {
                return (bool) $definition($value);
            }
        } else {
            return (bool) $definition($this->entity);
        }
    }

}