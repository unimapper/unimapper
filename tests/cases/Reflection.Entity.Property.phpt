<?php

use Tester\Assert,
    UniMapper\EntityCollection,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityPropertyTest extends UniMapper\Tests\TestCase
{

    private function _createReflection(
        $definition,
        $entityClass = "UniMapper\Tests\Fixtures\Entity\Simple"
    ) {
        return new Reflection\Entity\Property(
            $definition,
            new Reflection\Entity($entityClass)
        );
    }

    public function testvalidateValueType()
    {
        // Integer
        $this->_createReflection('integer $id m:primary')
            ->validateValueType(1);

        // String
        $this->_createReflection('string $test')->validateValueType("text");

        // DateTime
        $this->_createReflection('DateTime $time')
            ->validateValueType(new DateTime);

        // Collection
        $this->_createReflection('NoAdapter[] $collection')
            ->validateValueType(
                new EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple")
            );
    }

    public function testConvertValue()
    {
        // string -> integer
        Assert::same(1, $this->_createReflection('integer $id m:primary')->convertValue("1"));

        // integer -> string
        Assert::same("1", $this->_createReflection('string $test')->convertValue(1));

        // string -> datetime
        Assert::same(
            "02. 01. 2012",
            $this->_createReflection('DateTime $time')
                ->convertValue("2012-02-01")
                ->format("m. d. Y")
        );

        // array -> datetime
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime $time')
                ->convertValue(["date" => "2012-02-01"])
        );

        // object -> datetime
        $dateTime = new \stdClass;
        $dateTime->date = "2012-02-01";
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime $time')->convertValue($dateTime)
        );

        // string -> boolean
        Assert::same(
            true,
            $this->_createReflection('boolean $true')->convertValue("true")
        );
        Assert::false(
            $this->_createReflection('boolean $false')->convertValue("false")
        );

        // array -> collection
        $data = [
            ["url" => "http://example.com"],
            ["url" => "http://johndoe.com"]
        ];
        $collection = $this->_createReflection('Simple[] $collection')
            ->convertValue($data);
        Assert::type("UniMapper\EntityCollection", $collection);
        Assert::same(2, count($collection));
        Assert::isEqual("http://example.com", $collection[0]->url);
        Assert::isEqual("http://johndoe.com", $collection[1]->url);

        // array -> entity
        $entity = $this->_createReflection('Simple $entity')
            ->convertValue(["id" => "8"]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same(8, $entity->id);
    }

    /**
     * @throws Exception Can not convert value on property 'collection' automatically!
     */
    public function testCanNotConvertValue()
    {
        $this->_createReflection('Simple[] $collection')->convertValue("foo");
    }

    public function testReadonly()
    {
        Assert::false(
            $this->_createReflection('-read string $readonly')->isWritable()
        );
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected DateTime but string given on property time!
     */
    public function testInvalidInteger()
    {
        $this->_createReflection('DateTime $time')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected string but integer given on property test!
     */
    public function testInvalidString()
    {
        $this->_createReflection('string $test')->validateValueType(1);
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected DateTime but string given on property time!
     */
    public function testInvalidDateTime()
    {
        $this->_createReflection('DateTime $time')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyValueException Expected integer but string given on property id!
     */
    public function testInvalidCollection()
    {
        $this->_createReflection('integer $id m:primary')
            ->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyException Unsupported type 'UniMapper\Tests\Fixtures\Entity\Simple'!
     */
    public function testUnsupportedClasses()
    {
        $this->_createReflection(
            'UniMapper\Tests\Fixtures\Entity\Simple $entity'
        );
    }

    public function testIsTypeEntity()
    {
        Assert::true(
            $this->_createReflection(
                'Simple $entity'
            )->isTypeEntity()
        );
        Assert::false(
            $this->_createReflection('integer $id m:primary')->isTypeEntity()
        );
    }

    public function testAssocManyToMany()
    {
        // Local
        $local = $this->_createReflection('Simple[] $manyToMany m:assoc(M:N=sourceId|source_target|targetId)');
        Assert::type("UniMapper\Association\ManyToMany", $local->getAssociation());
        Assert::true($local->isAssociation());
        Assert::false($local->getAssociation()->isRemote());
        Assert::same("FooAdapter", $local->getAssociation()->getTargetAdapterName());

        // Remote
        $remote = $this->_createReflection('Remote[] $manyToMany m:assoc(M:N=localId|local_remote|remoteId)');
        Assert::true($remote->getAssociation()->isRemote());
        Assert::true($remote->getAssociation()->isDominant());
        Assert::same("RemoteAdapter", $remote->getAssociation()->getTargetAdapterName());

        // Remote - not dominant
        $remoteNotDominant = $this->_createReflection('Remote[] $manyToMany m:assoc(M<N=localId|local_remote|remoteId)');
        Assert::true($remoteNotDominant->getAssociation()->isRemote());
        Assert::false($remoteNotDominant->getAssociation()->isDominant());
    }

    public function testAssocOneToMany()
    {
        $property = $this->_createReflection('Simple[] $oneToMany m:assoc(1:N=sourceId)');
        Assert::true($property->isAssociation());
        Assert::type("UniMapper\Association\OneToMany", $property->getAssociation());
    }

    public function testAssocOneToOne()
    {
        $property = $this->_createReflection('Simple $oneToOne m:assoc(1:1=targetId)');
        Assert::true($property->isAssociation());
        Assert::type("UniMapper\Association\OneToOne", $property->getAssociation());
    }

}

$testCase = new ReflectionEntityPropertyTest;
$testCase->run();