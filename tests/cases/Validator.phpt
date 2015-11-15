<?php

use Tester\Assert;
use UniMapper\Validator;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ValidatorTest extends TestCase
{

    /** @var \UniMapper\Validator $validator */
    private $validator;

    /** @var Entity */
    private $entity;

    public function setUp()
    {
        $this->entity = new Entity;
        $this->validator = new Validator($this->entity);
    }

    public function testNoRules()
    {
        Assert::true($this->validator->on("id")->validate());
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
                ->addRule(function(UniMapper\Entity\Collection $collection) {
                    return count($collection) > 2;
                }, "Collection must contain two items at least!");

        $entity = $this->validator->getEntity();
        $entity->collection[] = new Entity;
        $entity->collection['testIndex'] = new Entity;
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
        $this->entity->collection[] = new Entity;

        $this->validator
            ->on("id")
                ->addRule(Validator::FILLED, "Id is required!")
            ->on("collection", "text")
                ->addRule(Validator::FILLED, "Text on nested collection must be filled!", Validator\Rule::WARNING)
            ->on("text")
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

        Assert::same("Text on nested collection must be filled!", $messages[1]->message);
        Assert::same(Validator\Rule::WARNING, $messages[1]->severity);
        Assert::same(["collection", 0, "text"], $messages[1]->path);

        Assert::same("This is just info!", $messages[2]->message);
        Assert::same(Validator\Rule::INFO, $messages[2]->severity);
        Assert::same(["text"], $messages[2]->path);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Condition can be called only on properties!
     */
    public function testConditionMustBeOnProperty()
    {
        $this->validator->addCondition(Validator::FILLED);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Unknown property 'undefined'!
     */
    public function testOnUndefined()
    {
        $this->validator->on("undefined");
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Validation can not be used on computed property!
     */
    public function testOnComputed()
    {
        $this->validator->on("computed");
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
            ->on("number")
                ->addCondition(Validator::FILLED)
                    ->addRule(function($value) {
                        return $value >= 1 && $value <= 5;
                    }, "Number must be a from 1 to 5!");

        // Success
        $entity = $this->validator->getEntity();
        $entity->email = "john.doe@example.com";
        Assert::true($this->validator->validate());
        Assert::same([], $this->validator->getMessages());

        // Fail
        $entity->email = "wrongemail";
        $entity->ip = "wrongip";
        $entity->url = "wrongurl";
        $entity->number = 6;
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
                    'number' => array(
                        new Validator\Message('Number must be a number from 1 to 5!', 1)
                    )
                )
            ),
            $this->validator->getMessages()
        );
    }

    public function testNestedValidators()
    {
        $nested = new Entity;
        $nested->getValidator()
            ->on("text")
                ->addRule(Validator::EMAIL, "Text must be e-mail!");

        // Get even deeper
        $nested->entity = new Entity;
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

/**
 * @property int      $id
 * @property string   $text
 * @property string   $ip
 * @property string   $url
 * @property string   $email
 * @property int      $number
 * @property Entity[] $collection
 * @property Entity   $entity
 * @property int      $computed   m:computed
 */
class Entity extends \UniMapper\Entity
{
    private function computeComputed()
    {
        return 1;
    }
}

$testCase = new ValidatorTest;
$testCase->run();