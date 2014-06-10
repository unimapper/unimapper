<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

class ReflectionPropertyTest extends Tester\TestCase
{

    private function createPropertyReflection($definition, $entityClass = "UniMapper\Tests\Fixtures\Entity\Simple")
    {
        return new UniMapper\Reflection\Entity\Property($definition, new UniMapper\Reflection\Entity($entityClass));
    }

    public function testValidateValue()
    {
        // Integer
        $this->createPropertyReflection('integer $id m:primary')->validateValue(1);

        // String
        $this->createPropertyReflection('string $test')->validateValue("text");

        // DateTime
        $this->createPropertyReflection('DateTime $time')->validateValue(new DateTime);

        // Collection
        $this->createPropertyReflection('NoMapper[] $collection')->validateValue(new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple"));
    }

    public function testConvertValue()
    {
        // string -> integer
        Assert::same(1, $this->createPropertyReflection('integer $id m:primary')->convertValue("1"));

        // integer -> string
        Assert::same("1", $this->createPropertyReflection('string $test')->convertValue(1));

        // string -> datetime
        Assert::same("02. 01. 2012", $this->createPropertyReflection('DateTime $time')->convertValue("2012-02-01")->format("m. d. Y"));

        // array -> collection
        $data = [
            ["url" => "http://example.com"],
            ["url" => "http://johndoe.com"]
        ];
        $collection = $this->createPropertyReflection('Simple[] $collection')->convertValue($data);
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
        $this->createPropertyReflection('Simple[] $collection')->convertValue("foo");
    }

    public function testReadonly()
    {
        Assert::false($this->createPropertyReflection('-read string $readonly')->isWritable());
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Expected DateTime but string given on property time!
     */
    public function testInvalidInteger()
    {
        $this->createPropertyReflection('DateTime $time')->validateValue("foo");
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Expected string but integer given on property test!
     */
    public function testInvalidString()
    {
        $this->createPropertyReflection('string $test')->validateValue(1);
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Expected DateTime but string given on property time!
     */
    public function testInvalidDateTime()
    {
        $this->createPropertyReflection('DateTime $time')->validateValue("foo");
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Expected integer but string given on property id!
     */
    public function testInvalidcollection()
    {
        $this->createPropertyReflection('integer $id m:primary')->validateValue("foo");
    }

    /**
     * @throws UniMapper\Exceptions\PropertyException Validation method validateUndefined not defined in UniMapper\Tests\Fixtures\Entity\Simple!
     */
    public function testUndefinedValidationMethod()
    {
        $this->createPropertyReflection('string $test m:validate(undefined)')->validateValue("foo");
    }

    /**
     * @throws UniMapper\Exceptions\PropertyTypeException Unsupported type 'UniMapper\Tests\Fixtures\Entity\Simple'!
     */
    public function testUnsupportedClasses()
    {
        $this->createPropertyReflection('UniMapper\Tests\Fixtures\Entity\Simple $entity');
    }

}

$testCase = new ReflectionPropertyTest;
$testCase->run();