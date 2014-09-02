<?php

namespace UniMapper;

/**
 * Validates entity
 */
class Validator
{

    const FILLED = "isFilled",
          URL = "isUrl",
          IP = "isIp",
          EMAIL = "isEmail";

    /** @var array $rules Set of rules and conditions */
    protected $rules = [];

    /** @var array $errors Failed important rules */
    protected $errors = [];

    /** @var array $warnings Failed rules with minor severity */
    protected $warnings = [];

    /** @var \UniMapper\Validator $parent Parent validator */
    protected $parent = [];

    /** @var \UniMapper\Reflection\Entity\Property $property */
    protected $property;

    /** @var \UniMapper\Entity $entity */
    protected $entity;

    /** @var string $child Child property name */
    protected $child;

    public function __construct(Entity $entity, Validator $parent = null)
    {
        $this->parent = $parent;
        $this->entity = $entity;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Add validation condition
     *
     * @param mixed  $validation Callable or some default validation method name
     *
     * @return \UniMapper\Validator
     */
    public function addCondition($validation)
    {
        if (!$this->property) {
            throw new \Exception("Condition must be called only on properties!");
        }
        $condition = new Validator\Condition(
            $this->_getValidation($validation),
            $this
        );
        $this->rules[] = $condition;
        return $condition->getValidator();
    }

    public function endCondition()
    {
        if ($this->parent) {
            return $this->parent;
        }
        return $this;
    }

    /**
     * Add permanent error manually
     *
     * @param string  $message
     * @param integer $severity
     * @param array   $indexes Failed indexes, has effect on collections only
     *
     * @return \UniMapper\Validator
     */
    public function addError($message, $severity = Validator\Rule::ERROR,
        array $indexes = []
    ) {
        $rule = new Validator\Rule(
            $this->entity,
            function () {
                return false;
            },
            $message,
            $this->property,
            $severity,
            $this->child
        );
        $rule->setChildFailed($indexes);
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Set property which is validation configured on
     *
     * @param string $name  Property name in entity
     * @param string $child Child property name
     *
     * @return \UniMapper\Validator
     *
     * @throws \Exception
     */
    public function on($name, $child = null)
    {
        if (!$this->entity->getReflection()->hasProperty($name)) {
            throw new \Exception("Unknown property '" . $name . "'!");
        }
        $this->property = $this->entity->getReflection()->getProperty($name);

        if ($this->property->isComputed()) {
            throw new \Exception(
                "Validation can not be applied on computed properties!"
            );
        }
        if ($child
            && (!$this->property->isTypeEntity()
            && !$this->property->isTypeCollection())
        ) {
            throw new \Exception(
                "Child validation can be used only on entities and collections!"
            );
        }
        $this->child = $child;

        return $this->endCondition();
    }

    /**
     * Start to configure validations on entity itself
     *
     * @return \UniMapper\Validator
     */
    public function onEntity()
    {
        $this->property = null;
        $this->child = null;
        return $this->endCondition();
    }

    /**
     * Addd validation rule
     *
     * @param mixed   $validation Callable or some default validation method name
     * @param string  $message
     * @param integer $severity
     *
     * @return \UniMapper\Validator
     */
    public function addRule($validation, $message, $severity = Validator\Rule::ERROR)
    {
        $this->rules[] = new Validator\Rule(
            $this->entity,
            $this->_getValidation($validation),
            $message,
            $this->property,
            $severity,
            $this->child
        );
        return $this;
    }

    private function _getValidation($definition)
    {
        if (is_string($definition) && method_exists(__CLASS__, $definition)) {
            return [__CLASS__, $definition];
        }
        return $definition;
    }

    /**
     * Run all validations
     *
     * @param integer $failOn Severity level
     *
     * @return boolean
     */
    public function validate($failOn = Validator\Rule::ERROR)
    {
        $this->warnings = [];
        $this->errors = [];

        foreach ($this->rules as $rule) {

            if ($rule instanceof Validator\Condition) {
                // Condition

                $condition = $rule;
                if ($condition->validate()) {
                    // Conditions rules

                    if (!$condition->getValidator()->validate($failOn)) {

                        $this->errors = array_merge(
                            $this->errors,
                            $rule->getValidator()->getErrors()
                        );
                    }
                    $this->warnings = array_merge(
                        $this->warnings,
                        $rule->getValidator()->getWarnings()
                    );
                }
            } else {
                // Rule

                if (!$rule->validate()) {

                    if ($rule->getSeverity() <= $failOn) {
                        $this->errors[] = $rule;
                    } else {
                        $this->warnings[] = $rule;
                    }
                }
            }
        }

        // Run nested validators - every entity may have its own validator
        foreach ($this->entity->getData() as $propertyName => $value) {

            if ($value instanceof Entity) {

                $validator = $value->getValidator();

                if (!$validator->validate($failOn)) {

                    foreach ($validator->getErrors() as $error) {

                        $rule = clone $error;
                        $rule->setPath(
                            array_merge([$propertyName], $rule->getPath())
                        );
                        $this->errors[] = $rule;
                    }
                }

                foreach ($validator->getWarnings() as $warning) {

                    $rule = clone $warning;
                    $rule->setPath(
                        array_merge([$propertyName], $rule->getPath())
                    );
                    $this->warnings[] = $rule;
                }
            }
        }

        return count($this->errors) === 0;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Get messages
     *
     * @param integer  $minSeverity
     * @param callable $factory
     *
     * @return array
     */
    public function getMessages(
        $minSeverity = Validator\Rule::DEBUG,
        callable $factory = null
    ) {
        if ($factory === null) {
            $factory = function ($text, $severity, $path) {
                return new Validator\Message($text, $severity, $path);
            };
        }

        $messages = [];
        foreach (array_merge($this->errors, $this->warnings) as $rule) {

            if ($rule->getSeverity() <= $minSeverity) {

                if ($rule->getProperty() !== null
                    && $rule->getProperty()->isTypeCollection()
                ) {
                    foreach ($rule->getFailedChildIndexes() as $index) {

                        $path = $rule->getPath();
                        if (count($path) > 1) {

                            $leaf = array_pop($path);
                            $path = array_merge($path, [$index, $leaf]);
                        } else {
                            $path[] = $index;
                        }

                        $messages[] = $factory(
                            $rule->getMessage(),
                            $rule->getSeverity(),
                            $path
                        );
                    }
                } else {
                    $messages[] = $factory(
                        $rule->getMessage(),
                        $rule->getSeverity(),
                        $rule->getPath()
                    );
                }
            }
        }

        return $messages;
    }

    public static function isTraversable($value)
    {
        return is_array($value) || is_object($value);
    }

    public static function isUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    public static function isFilled($value)
    {
        if (self::isTraversable($value)) {
            return count($value) > 0;
        }
        return !empty($value);
    }

    public static function isEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    public static function isIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    public static function isIpv4($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function isIpv6($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

}
