<?php

use Tester\Assert;
use UniMapper\Entity;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/common/AdapterConvention.php';

/**
 * @testCase
 */
class EntityReflectionPropertyTest extends TestCase
{

    const ENUM1 = 1,
          ENUM2 = 2;

    private function _createReflection(
        $type,
        $name,
        $options = null,
        $readonly = false,
        $entityClass = "Foo"
    ) {
        return new Entity\Reflection\Property(
            $type,
            $name,
            new Entity\Reflection($entityClass),
            $readonly,
            $options
        );
    }

    public function testValidateValueType()
    {
        // Integer
        $this->_createReflection('integer', 'id')
            ->validateValueType(1);

        // String
        $this->_createReflection('string', 'test')->validateValueType("text");

        // DateTime
        $this->_createReflection('DateTime', 'time')
            ->validateValueType(new DateTime);

        // Collection
        $this->_createReflection('Foo[]', 'collection')
            ->validateValueType(
                new Entity\Collection("Foo")
            );

        // Enumeration
        $this->_createReflection('integer', "enum", "m:enum(" . get_class() . "::ENUM*)")
            ->validateValueType(1);

        // Primary
        Entity\Reflection::load("Foo")->getProperty("id")->validateValueType(0);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Value 3 is not from defined entity enumeration range on property enum!
     */
    public function testValidateValueTypeInvalidEnum()
    {
        // Enumeration
        $this->_createReflection('integer', "enum", "m:enum(" . get_class() . "::ENUM*)")
            ->validateValueType(3);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Primary value can not be empty string or null!
     */
    public function testValidateValueTypePrimaryNull()
    {
        Entity\Reflection::load("Foo")
            ->getProperty("id")
            ->validateValueType(null);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Primary value can not be empty string or null!
     */
    public function testValidateValueTypePrimaryEmptyString()
    {
        Entity\Reflection::load("Foo")->getProperty("id")->validateValueType("");
    }

    public function testConvertValue()
    {
        // string -> integer
        Assert::same(
            1,
            $this->_createReflection('integer',  'id')->convertValue("1")
        );
        Assert::null(
            $this->_createReflection('integer', 'id')->convertValue("")
        );

        // integer -> string
        Assert::same(
            "1",
            $this->_createReflection('string', 'test')->convertValue(1)
        );

        // string -> datetime
        Assert::same(
            "02. 01. 2012",
            $this->_createReflection('DateTime', 'time')
                ->convertValue("2012-02-01")
                ->format("m. d. Y")
        );
        Assert::null(
            $this->_createReflection('DateTime', 'time')->convertValue("")
        );

        // array -> datetime
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime', 'time')
                ->convertValue(["date" => "2012-02-01"])
        );

        // object -> datetime
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime', 'time')->convertValue(
                (object) ["date" => "2012-02-01"]
            )
        );

        // array -> date
        Assert::type(
            "DateTime",
            $this->_createReflection('Date', 'date')
                ->convertValue(["date" => "2012-02-01"])
        );

        // object -> date
        Assert::type(
            "DateTime",
            $this->_createReflection('Date', 'date')->convertValue(
                (object) ["date" => "2012-02-01"]
            )
        );

        // string -> boolean
        Assert::same(
            true,
            $this->_createReflection('boolean', 'true')->convertValue("true")
        );
        Assert::false(
            $this->_createReflection('boolean', 'false')->convertValue("false")
        );
        Assert::null(
            $this->_createReflection('boolean', 'false')->convertValue("")
        );

        // array -> collection
        $collection = $this->_createReflection('Foo[]', 'collection')
            ->convertValue([["id" => 1]]);
        Assert::type("UniMapper\Entity\Collection", $collection);
        Assert::same(1, count($collection));
        Assert::same(1, $collection[0]->id);

        // array -> entity
        $entity = $this->_createReflection('Foo', 'entity')
            ->convertValue(["id" => "8"]);

        Assert::type("Foo", $entity);
        Assert::same(8, $entity->id);
    }

    public function testConvertValueNull()
    {
        Assert::null($this->_createReflection('string', 'id')->convertValue(null));
        Assert::null($this->_createReflection('integer', 'id')->convertValue(null));
        Assert::null($this->_createReflection('array', 'id')->convertValue(null));
        Assert::null($this->_createReflection('boolean', 'id')->convertValue(null));
        Assert::null($this->_createReflection('Foo', 'id')->convertValue(null));
        Assert::null($this->_createReflection('Foo[]', 'id')->convertValue(null));
        Assert::null($this->_createReflection('DateTime', 'id')->convertValue(null));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Can not convert value on property 'collection' automatically!
     */
    public function testConvertValueFailed()
    {
        $this->_createReflection('Foo[]', 'collection')->convertValue("foo");
    }

    public function testGetRelatedFiles()
    {
        Assert::same(
            [Entity\Reflection::load("Foo")->getFileName()],
            Entity\Reflection::load("Foo")->getRelatedFiles()
        );
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected double but integer given on property test!
     */
    public function testValidateValueTypeInvalidDouble()
    {
        $this->_createReflection('double', 'test')->validateValueType(0);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected string but integer given on property test!
     */
    public function testValidateValueTypeInvalidString()
    {
        $this->_createReflection('string', 'test')->validateValueType(1);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected DateTime but string given on property time!
     */
    public function testValidateValueTypeInvalidDateTime()
    {
        $this->_createReflection('DateTime', 'time')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Expected integer but string given on property id!
     */
    public function testValidateValueTypeInvalidInteger()
    {
        $this->_createReflection('integer', 'id')->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyException Unsupported type 'Test\Foo'!
     */
    public function testTypeUnsupportedClass()
    {
        $this->_createReflection('Test\Foo', 'entity');
    }

    public function testTypeEntity()
    {
        Assert::same(
            Entity\Reflection\Property::TYPE_ENTITY,
            $this->_createReflection('Foo', 'entity')->getType()
        );
        Assert::false(
            $this->_createReflection('integer', 'id')->getType() === Entity\Reflection\Property::TYPE_ENTITY
        );
    }

    public function testTypeAliases()
    {
        Assert::same(
            Entity\Reflection\Property::TYPE_INTEGER,
            $this->_createReflection('int', 'name')->getType()
        );
        Assert::same(
            Entity\Reflection\Property::TYPE_BOOLEAN,
            $this->_createReflection('bool', 'name')->getType()
        );
        Assert::same(
            Entity\Reflection\Property::TYPE_DOUBLE,
            $this->_createReflection('real', 'name')->getType()
        );
        Assert::same(
            Entity\Reflection\Property::TYPE_DOUBLE,
            $this->_createReflection('float', 'name')->getType()
        );
    }

    /**
     * @throws UniMapper\Exception\PropertyException Unsupported type 'inTeger'!
     */
    public function testTypeCaseSensitivity()
    {
        $this->_createReflection('inTeger', 'name');
    }

    /**
     * @throws UniMapper\Exception\PropertyException Unsupported type 'Int'!
     */
    public function testTypeCaseSensitivityAliases()
    {
        $this->_createReflection('Int', 'name');
    }

    public function testGetName()
    {
        Assert::same("id", $this->_createReflection("int", "id")->getName());
    }

    /**
     * @throws Exception Mapping is disabled!
     */
    public function testGetUnmappedDisabledMapping()
    {
        Assert::same(
            "id",
            $this->_createReflection("int", "disabledMap", "m:map(false)")
                ->getUnmapped()
        );
    }

    public function testGetUnmappedConvention()
    {
        \UniMapper\Convention::registerAdapterConvention("Foo", new AdapterConvention);
        Assert::same("foo_bar", $this->_createReflection("int", "fooBar")->getUnmapped());
    }

}

/**
 * @adapter Foo
 *
 * @property int $id m:primary
 */
class Foo extends \UniMapper\Entity {}

$testCase = new EntityReflectionPropertyTest;
$testCase->run();