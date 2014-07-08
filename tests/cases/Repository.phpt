<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

class RepositoryTest extends Tester\TestCase
{

    /** @var \UniMapper\Repository $repository */
    private $repository;

    /** @var \Mockista\Mock $mapperMock */
    private $mapperMock;

    public function setUp()
    {
        $this->repository = new Fixtures\Repository\SimpleRepository;

        $mockista = new \Mockista\Registry;
        $this->mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
        $this->mapperMock->expects("getName")->once()->andReturn("FooMapper");
    }

    public function testGetName()
    {
        Assert::same("Simple", $this->repository->getName());
    }

    public function testGetEntityName()
    {
        Assert::same("Simple", $this->repository->getEntityName());
    }

    public function testQuery()
    {
        $this->repository->registerMapper(
            new Fixtures\Mapper\Simple("FooMapper")
        );
        Assert::type("UniMapper\QueryBuilder", $this->repository->query());
    }

    public function testCreateEntity()
    {
        // Autodetect entity
        $entity = $this->repository->createEntity(
            ["text" => "foo", "publicProperty" => "foo"]
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same("foo", $entity->text);
        Assert::same("foo", $entity->publicProperty);

        // Force entity
        $nestedEntity = $this->repository->createEntity(
            ["text" => "foo"],
            "Nested"
        );
        Assert::type("UniMapper\Tests\Fixtures\Entity\Nested", $nestedEntity);
        Assert::same("foo", $nestedEntity->text);
    }

    public function testSaveUpdate()
    {
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo", "id" => 1]);
        $this->mapperMock->expects("updateOne")->once()->with("resource", "id", 1,["text" => "foo", "id" => 1]);
        $this->mapperMock->freeze();
        $this->repository->registerMapper($this->mapperMock);

        $entity = new Fixtures\Entity\Simple;
        $entity->id = 1;
        $entity->text = "foo";
        $this->repository->save($entity);
    }

    public function testSaveInsert()
    {
        $this->mapperMock->expects("unmapEntity")->once()->andReturn(["text" => "foo", "id" => 1]);
        $this->mapperMock->expects("insert")->once()->with("resource", ["text" => "foo", "id" => 1])->andReturn(["id" => 1]);
        $this->mapperMock->expects("mapValue")->once()->andReturn(1);
        $this->mapperMock->freeze();
        $this->repository->registerMapper($this->mapperMock);

        $entity = new Fixtures\Entity\Simple;
        $entity->text = "foo";
        $this->repository->save($entity);
    }

    public function testSaveInvalid()
    {
        $entity = new Fixtures\Entity\Simple;
        $entity->text = "foo";
        $entity->email = "invalidemail";
        $entity->getValidator()->on("email")->addRule(\UniMapper\Validator::EMAIL, "Invalid e-mail format!");

        try {
            $this->repository->save($entity);
        } catch (UniMapper\Exception\ValidatorException $e) {
            $validator = $e->getValidator();
        }

        Assert::isEqual(
            [
                'properties' => [
                    'email' => [
                        new \UniMapper\Validator\Message('Invalid e-mail format!', 1)
                    ]
                ]
            ],
            (array) $validator->getMessages()
        );
    }

    /**
     * @throws UniMapper\Exception\RepositoryException Primary value in entity 'Simple' must be set!
     */
    public function testDeletNoPrimaryValue()
    {
        $this->mapperMock->freeze();
        $this->repository->registerMapper($this->mapperMock);

        $entity = new Fixtures\Entity\Simple;
        $this->repository->delete($entity);
    }

    public function testDelete()
    {
        $this->mapperMock->expects("delete")->with("resource", [["id", "=", 1, "AND"]])->once();
        $this->mapperMock->freeze();
        $this->repository->registerMapper($this->mapperMock);

        $entity = new Fixtures\Entity\Simple;
        $entity->id = 1;
        $this->repository->delete($entity);
    }

}

$testCase = new RepositoryTest;
$testCase->run();