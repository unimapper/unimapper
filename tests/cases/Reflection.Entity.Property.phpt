<?php

use Tester\Assert,
    UniMapper\EntityCollection,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

class ReflectionEntityPropertyTest extends Tester\TestCase
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
     * @throws UniMapper\Exception\PropertyValidationException Expected DateTime but string given on property time!
     */
    public function testInvalidInteger()
    {
        $this->_createReflection('DateTime $time')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyValidationException Expected string but integer given on property test!
     */
    public function testInvalidString()
    {
        $this->_createReflection('string $test')->validateValueType(1);
    }

    /**
     * @throws UniMapper\Exception\PropertyValidationException Expected DateTime but string given on property time!
     */
    public function testInvalidDateTime()
    {
        $this->_createReflection('DateTime $time')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyValidationException Expected integer but string given on property id!
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

}

$testCase = new ReflectionEntityPropertyTest;
$testCase->run();