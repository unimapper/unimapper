<?php

use Tester\Assert,
    UniMapper\Validator;

require __DIR__ . '/../bootstrap.php';

class ValidatorTest extends UniMapper\Tests\TestCase
{

    /** @var \UniMapper\Validator $validator */
    private $validator;

    /** @var \UniMapper\Tests\Fixtures\Entity\Simple */
    private $entity;

    public function setUp()
    {
        $this->entity = $this->createEntity("Simple");
        $this->validator = new Validator($this->entity);
    }

    public function testOnEntity()
    {
        $this->validator
            ->on("text")
                ->addError("Text error")
            ->onEntity()
                ->addError("Global error");

        $this->validator->validate();
        Assert::isEqual(
            array(
                'properties' => array(
                    'text' => array(
                        new Validator\Message('Text error', 1)
                    )
                ),
                'global' => array(
                    new Validator\Message('Global error', 1)
                )
            ),
            $this->validator->getMessages()
        );
    }

    public function testAddRule()
    {
        $this->validator
            ->addRule(function(\UniMapper\Entity $entity) {
                return $entity->text === "foo" && $entity->id === 1;
            }, "Global rule not passed!")
            ->on("text")
                ->addRule(Validator::FILLED, "Text is required!")
            ->on("email")
                ->addRule(Validator::EMAIL, "Invalid e-mail format!");

        // Fail
        $entity = $this->validator->getEntity();
        $entity->email = "wrongemail";
        Assert::false($this->validator->validate());
        Assert::isEqual(
            array(
                'global' => array(
                    new Validator\Message('Global rule not passed!', 1)
                ),
                'properties' => array(
                    'text' => array(
                        new Validator\Message('Text is required!', 1)
                    ),
                    'email' => array(
                        new Validator\Message('Invalid e-mail format!', 1)
                    )
                )
            ),
            $this->validator->getMessages()
        );

        // Success
        $entity->email = "john.doe@example.com";
        $entity->text = "foo";
        $entity->id = 1;
        Assert::true($this->validator->validate());
        Assert::same([], $this->validator->getMessages());
    }

    public function testChildCollection()
    {
        $this->validator
            ->on("collection", "text")
                ->addRule(Validator::FILLED, "Invalid text in child collection!")
                ->addRule(Validator::EMAIL, "Text must be valid e-mail format!")
                ->addRule(function($value, \UniMapper\Entity $entity, $index) {
                    return $index === 0;
                }, "Not first entity!")
            ->on("collection")
                ->addRule(function(UniMapper\EntityCollection $collection) {
                    return count($collection) > 2;
                }, "Collection must contain two items at least!");

        $entity = $this->validator->getEntity();
        $entity->collection[] = $this->createEntity("Nested");
        $entity->collection['testIndex'] = $this->createEntity("Nested");
        $this->validator->validate();

        Assert::isEqual(
            array(
                'properties' => array(
                    'collection' => array(
                        'text' => array(
                            array(
                                new Validator\Message('Invalid text in child collection!', 1),
                                new Validator\Message('Text must be valid e-mail format!', 1)
                            ),
                            'testIndex' => array(
                                new Validator\Message('Invalid text in child collection!', 1),
                                new Validator\Message('Text must be valid e-mail format!', 1),
                                new Validator\Message('Not first entity!', 1)
                            )
                        ),
                        new Validator\Message('Collection must contain two items at least!', 1)
                    )
                )
            ),
            $this->validator->getMessages()
        );
    }

    public function testGetMessages()
    {
        $this->validator
            ->on("id")
                ->addRule(Validator::FILLED, "Id is required!")
            ->on("email")
                ->addRule(Validator::EMAIL, "This is just info!", Validator\Rule::INFO);
        $this->validator->validate();

        $messages = $this->validator->getMessages(
            Validator\Rule::DEBUG,
            function($message, $severity, $path) {
                $object = new \stdClass;
                $object->message = $message;
                $object->severity = $severity;
                $object->path = $path;
                return $object;
            }
        );

        Assert::same("Id is required!", $messages[0]->message);
        Assert::same(Validator\Rule::ERROR, $messages[0]->severity);
        Assert::same(["id"], $messages[0]->path);

        Assert::same("This is just info!", $messages[1]->message);
        Assert::same(Validator\Rule::INFO, $messages[1]->severity);
        Assert::same(["email"], $messages[1]->path);
    }

    /**
     * @throws Exception Condition must be called only on properties!
     */
    public function testConditionMustBeOnProperty()
    {
        $validator = new Validator($this->createEntity("Simple"));
        $validator->addCondition(Validator::FILLED);
    }

    /**
     * @throws Exception Unknown property 'undefined'!
     */
    public function testOnUndefined()
    {
        $validator = new Validator($this->createEntity("Simple"));
        $validator->on("undefined");
    }

    /**
     * @throws Exception Validation can not be set on computed property!
     */
    public function testOnComputed()
    {
        $validator = new Validator($this->createEntity("Simple"));
        $validator->on("year");
    }

    public function testAddCondition()
    {
        $this->validator
            ->on("ip")
                ->addCondition(Validator::FILLED)
                    ->addRule(Validator::IP, "Invalid IP format!")
                ->endCondition()
            ->on("email")
                ->addRule(Validator::FILLED, "E-mail is requried!")
                ->addRule(Validator::EMAIL, "Invalid e-mail format!")
            ->on("url")
                ->addCondition(Validator::FILLED)
                    ->addRule(Validator::URL, "Invalid URL format!")
                ->endCondition()
            ->on("mark")
                ->addCondition(Validator::FILLED)
                    ->addRule(function($value) {
                        return $value >= 1 && $value <= 5;
                    }, "Mark must be a number from 1 to 5!");

        // Success
        $entity = $this->validator->getEntity();
        $entity->email = "john.doe@example.com";
        Assert::true($this->validator->validate());
        Assert::same([], $this->validator->getMessages());

        // Fail
        $entity->email = "wrongemail";
        $entity->ip = "wrongip";
        $entity->url = "wrongurl";
        $entity->mark = 35;
        Assert::false($this->validator->validate());
        Assert::isEqual(
            array(
                'properties' => array(
                    'ip' => array(
                        new Validator\Message('Invalid IP format!', 1)
                    ),
                    'email' => array(
                        new Validator\Message('Invalid e-mail format!', 1)
                    ),
                    'url' => array(
                        new Validator\Message('Invalid URL format!', 1)
                    ),
                    'mark' => array(
                        new Validator\Message('Mark must be a number from 1 to 5!', 1)
                    )
                )
            ),
            $this->validator->getMessages()
        );
    }

    public function testNestedValidators()
    {
        $nested = $this->createEntity("Nested");
        $nested->getValidator()
            ->on("text")
                ->addRule(Validator::EMAIL, "Text must be e-mail!");

        // Get even deeper
        $nested->entity = $this->createEntity("Simple");
        $nested->entity->getValidator()
                ->on("url")
                    ->addRule(Validator::URL, "Invalid URL!");

        $this->entity->entity = $nested;

        Assert::false($this->validator->validate());

        $messages = $this->validator->getMessages();
        Assert::count(2, $messages);
        Assert::same(['entity', 'text'], $messages[0]->getPath());
        Assert::same("Text must be e-mail!", $messages[0]->getText());
        Assert::same(['entity', 'entity', 'url'], $messages[1]->getPath());
        Assert::same("Invalid URL!", $messages[1]->getText());
    }

}

$testCase = new ValidatorTest;
$testCase->run();