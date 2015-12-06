<?php

use UniMapper\Entity\Reflection\Property;
use UniMapper\Entity\Reflection\Property\Option\Assoc;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionPropertyOptionAssocTest extends TestCase
{

    private function createProperty($options = null, $type = "Foo")
    {
        return new Property($type, "name", Foo::getReflection(), false, $options);
    }

    public function testCreate()
    {
        $assoc = Assoc::create($this->createProperty(), "value", ["assoc-by" => "arg1|arg2"]);
        Assert::type("UniMapper\Entity\Reflection\Property\Option\Assoc", $assoc);
        Assert::same("value", $assoc->getType());
        Assert::same(["arg1", "arg2"], $assoc->getDefinition());
    }

    /**
     * @throws UniMapper\Exception\OptionException Association definition required!
     */
    public function testCreateNoValue()
    {
        Assoc::create($this->createProperty());
    }

    /**
     * @throws UniMapper\Exception\OptionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testAfterCreateMapping()
    {
        $property = $this->createProperty("m:map-by");
        Assoc::afterCreate(
            $property,
            Assoc::create($property, "value")
        );
    }

    /**
     * @throws UniMapper\Exception\OptionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testAfterCreateEnumeration()
    {
        $property = $this->createProperty("m:enum(self::ENUM_*)");
        Assoc::afterCreate(
            $property,
            Assoc::create($property, "value")
        );
    }

    /**
     * @throws UniMapper\Exception\OptionException Association can not be combined with mapping, computed or enumeration!
     */
    public function testAfterCreateComputed()
    {
        $property = $this->createProperty("m:computed");
        Assoc::afterCreate(
            $property,
            Assoc::create($property, "value")
        );
    }

    public function testConstruct()
    {
        $reflection = Foo::getReflection();
        $class = new Assoc("1:N", $reflection, $reflection, $this->createProperty(), []);
        Assert::same("1:n", $class->getType());
        Assert::same([], $class->getDefinition());
    }

    /**
     * @throws UniMapper\Exception\OptionException Can not use associations while source entity NoAdapter has no adapter defined!
     */
    public function testConstructSourceHasNoAdapter()
    {
        $reflection = NoAdapter::getReflection();
        new Assoc("type", $reflection, $reflection, $this->createProperty(), []);
    }


    /**
     * @throws UniMapper\Exception\OptionException Property type must be collection or entity if association defined!
     */
    public function testConstructTargetHasNoAdapter()
    {
        $reflection = Foo::getReflection();
        new Assoc("type", $reflection, $reflection, $this->createProperty(null, "int"), []);
    }

}

/**
 * @adapter FooAdapter
 *
 * @property int $id m:primary
 */
class Foo extends \UniMapper\Entity
{
    public function computeName() {}
}

/**
 * @adapter BarAdapter
 */
class Bar extends \UniMapper\Entity {}

class NoAdapter extends \UniMapper\Entity {}

$testCase = new EntityReflectionPropertyOptionAssocTest;
$testCase->run();