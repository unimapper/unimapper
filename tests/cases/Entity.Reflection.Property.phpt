<?php

use Tester\Assert;
use UniMapper\Entity;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class EntityReflectionPropertyTest extends \Tester\TestCase
{

    const ENUM1 = 1,
          ENUM2 = 2;

    private function _createReflection(
        $type,
        $name,
        $options = null,
        $readonly = false,
        $entityClass = "UniMapper\Tests\Fixtures\Entity\Simple"
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
        $this->_createReflection('integer', 'id', 'm:primary')
            ->validateValueType(1);

        // String
        $this->_createReflection('string', 'test')->validateValueType("text");

        // DateTime
        $this->_createReflection('DateTime', 'time')
            ->validateValueType(new DateTime);

        // Collection
        $this->_createReflection('NoAdapter[]', 'collection')
            ->validateValueType(
                new Entity\Collection("NoAdapter")
            );

        // Enumeration
        $this->_createReflection('integer', "enum", "m:enum(" . get_class() . "::ENUM*)")
            ->validateValueType(1);

        // Primary
        $this->_createReflection('integer', 'id', 'm:primary')->validateValueType(0);
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
        $this->_createReflection('integer', 'id', 'm:primary')->validateValueType(null);
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Primary value can not be empty string or null!
     */
    public function testValidateValueTypePrimaryEmptyString()
    {
        $this->_createReflection('string', 'id', 'm:primary')->validateValueType("");
    }

    public function testConvertValue()
    {
        // string -> integer
        Assert::same(1, $this->_createReflection('integer',  'id', 'm:primary')->convertValue("1"));
        Assert::null($this->_createReflection('integer', 'id')->convertValue(""));

        // integer -> string
        Assert::same("1", $this->_createReflection('string', 'test')->convertValue(1));

        // string -> datetime
        Assert::same(
            "02. 01. 2012",
            $this->_createReflection('DateTime', 'time')
                ->convertValue("2012-02-01")
                ->format("m. d. Y")
        );
        Assert::null($this->_createReflection('DateTime', 'time')->convertValue(""));

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
        $data = [
            ["url" => "http://example.com"],
            ["url" => "http://johndoe.com"]
        ];
        $collection = $this->_createReflection('Simple[]', 'collection')
            ->convertValue($data);
        Assert::type("UniMapper\Entity\Collection", $collection);
        Assert::same(2, count($collection));
        Assert::isEqual("http://example.com", $collection[0]->url);
        Assert::isEqual("http://johndoe.com", $collection[1]->url);

        // array -> entity
        $entity = $this->_createReflection('Simple', 'entity')
            ->convertValue(["id" => "8"]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same(8, $entity->id);
    }

    public function testConvertValueNull()
    {
        Assert::null($this->_createReflection('string', 'id')->convertValue(null));
        Assert::null($this->_createReflection('integer', 'id')->convertValue(null));
        Assert::null($this->_createReflection('array', 'id')->convertValue(null));
        Assert::null($this->_createReflection('boolean', 'id')->convertValue(null));
        Assert::null($this->_createReflection('Simple', 'id')->convertValue(null));
        Assert::null($this->_createReflection('Simple[]', 'id')->convertValue(null));
        Assert::null($this->_createReflection('DateTime', 'id')->convertValue(null));
    }

    /**
     * @throws UniMapper\Exception\InvalidArgumentException Can not convert value on property 'collection' automatically!
     */
    public function testConvertValueFailed()
    {
        $this->_createReflection('Simple[]', 'collection')->convertValue("foo");
    }

    public function testGetRelatedFiles()
    {
        Assert::same(
            array(
                Entity\Reflection::load("Simple")->getFileName(),
                Entity\Reflection::load("Nested")->getFileName(),
                Entity\Reflection::load("Remote")->getFileName()
            ),
            Entity\Reflection::load("Simple")->getRelatedFiles()
        );
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
        $this->_createReflection('integer', 'id', 'm:primary')
            ->validateValueType("foo");
    }

    /**
     * @throws UniMapper\Exception\PropertyException Unsupported type 'UniMapper\Tests\Fixtures\Entity\Simple'!
     */
    public function testTypeUnsupportedClasses()
    {
        $this->_createReflection(
            'UniMapper\Tests\Fixtures\Entity\Simple',
            'entity'
        );
    }

    public function testTypeEntity()
    {
        Assert::same(
            Entity\Reflection\Property::TYPE_ENTITY,
            $this->_createReflection(
                'Simple', 'entity'
            )->getType()
        );
        Assert::false(
            $this->_createReflection('integer', 'id', 'm:primary')->getType() === Entity\Reflection\Property::TYPE_ENTITY
        );
    }

    public function testTypeAliases()
    {
        Assert::same(Entity\Reflection\Property::TYPE_INTEGER, $this->_createReflection('int', 'name')->getType());
        Assert::same(Entity\Reflection\Property::TYPE_BOOLEAN, $this->_createReflection('bool', 'name')->getType());
        Assert::same(Entity\Reflection\Property::TYPE_DOUBLE, $this->_createReflection('real', 'name')->getType());
        Assert::same(Entity\Reflection\Property::TYPE_DOUBLE, $this->_createReflection('float', 'name')->getType());
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

    public function testPrimaryTypeBoolean()
    {
        Assert::exception(
            function() {
                $this->_createReflection('Date', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_DATE . "' given!"
        );
        Assert::exception(
            function() {
                $this->_createReflection('DateTime', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_DATETIME . "' given!"
        );
        Assert::exception(
            function() {
                $this->_createReflection('Simple', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_ENTITY . "' given!"
        );
        Assert::exception(
            function() {
                $this->_createReflection('Simple[]', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_COLLECTION . "' given!"
        );
        Assert::exception(
            function() {
                $this->_createReflection('boolean', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_BOOLEAN . "' given!"
        );
        Assert::exception(
            function() {
                $this->_createReflection('array', 'id', 'm:primary');
            },
            "UniMapper\Exception\PropertyException",
            "Primary property can be only double,integer,string but '" . Entity\Reflection\Property::TYPE_ARRAY . "' given!"
        );
    }

    public function testOptionAssocManyToMany()
    {
        // Local
        $local = $this->_createReflection('Simple[]', 'manyToMany', 'm:assoc(M:N) m:assoc-by(sourceId|source_target|targetId)');
        Assert::type("UniMapper\Association\ManyToMany", $local->getOption(Entity\Reflection\Property::OPTION_ASSOC));
        Assert::true($local->hasOption(Entity\Reflection\Property::OPTION_ASSOC));
        Assert::false($local->getOption(Entity\Reflection\Property::OPTION_ASSOC)->isRemote());
        Assert::same("FooAdapter", $local->getOption(Entity\Reflection\Property::OPTION_ASSOC)->getTargetAdapterName());

        // Remote
        $remote = $this->_createReflection('Remote[]', 'manyToMany', 'm:assoc(M:N) m:assoc-by(localId|local_remote|remoteId)')->getOption(Entity\Reflection\Property::OPTION_ASSOC);
        Assert::true($remote->isRemote());
        Assert::true($remote->isDominant());
        Assert::same("RemoteAdapter", $remote->getTargetAdapterName());
        Assert::same("localId", $remote->getJoinKey());
        Assert::same("local_remote", $remote->getJoinResource());
        Assert::same("remoteId", $remote->getReferencingKey());
        Assert::same("id", $remote->getTargetPrimaryKey());

        // Remote - not dominant
        $remoteNotDominant = $this->_createReflection('Remote[]', 'manyToMany', 'm:assoc(M<N) m:assoc-by(localId|local_remote|remoteId)');
        Assert::true($remoteNotDominant->getOption(Entity\Reflection\Property::OPTION_ASSOC)->isRemote());
        Assert::false($remoteNotDominant->getOption(Entity\Reflection\Property::OPTION_ASSOC)->isDominant());
    }

    public function testOptionAssocOneToMany()
    {
        $property = $this->_createReflection('Simple[]', 'oneToMany', 'm:assoc(1:N) m:assoc-by(sourceId)');
        Assert::true($property->hasOption("assoc"));
        Assert::type("UniMapper\Association\OneToMany", $property->getOption(Entity\Reflection\Property::OPTION_ASSOC));
    }

    public function testOptionAssocOneToOne()
    {
        $property = $this->_createReflection('Simple', 'oneToOne', 'm:assoc(1:1) m:assoc-by(targetId)');
        Assert::true($property->hasOption("assoc"));
        $association = $property->getOption(Entity\Reflection\Property::OPTION_ASSOC);
        Assert::type("UniMapper\Association\OneToOne", $association);
        Assert::same("simplePrimaryId", $association->getTargetPrimaryKey());
        Assert::same("targetId", $association->getReferencingKey());
        Assert::same("targetId", $association->getKey());
    }

    /**
     * @throws UniMapper\Exception\PropertyException Target entity must have defined primary when 1:1 relation used!
     */
    public function testOptionAssocOneToOneTargetNoPrimary()
    {
        $this->_createReflection('NoPrimary', 'oneToOne', 'm:assoc(1:1) m:assoc-by(targetId)');
    }

    public function testOptionAssocManyToOne()
    {
        $property = $this->_createReflection('Simple', 'manyToOne', 'm:assoc(N:1) m:assoc-by(targetId)');
        Assert::true($property->hasOption("assoc"));
        $association = $property->getOption(Entity\Reflection\Property::OPTION_ASSOC);
        Assert::type("UniMapper\Association\ManyToOne", $association);
        Assert::same("simplePrimaryId", $association->getTargetPrimaryKey());
        Assert::same("targetId", $association->getReferencingKey());
        Assert::same("targetId", $association->getKey());
    }

    /**
     * @throws UniMapper\Exception\PropertyException Target entity must have defined primary when N:1 relation used!
     */
    public function testOptionAssocManyToOneTargetNoPrimary()
    {
        $this->_createReflection('NoPrimary', 'manyToOne', 'm:assoc(N:1) m:assoc-by(remoteId)');
    }

    public function testOptionMap()
    {
        $reflection = $this->_createReflection('array', 'name', 'm:map-by(foo)');
        Assert::same("foo", $reflection->getName(true));
    }

    public function testOptionMapFilterWithEntityMethod()
    {
        $reflection = $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter(stringToArray|arrayToString)');
        Assert::same("foo", $reflection->getName(true));
        Assert::true(is_callable($reflection->getOption(Entity\Reflection\Property::OPTION_MAP_FILTER)[0]));
        Assert::true(is_callable($reflection->getOption(Entity\Reflection\Property::OPTION_MAP_FILTER)[1]));
    }

    public function testOptionMapFilterWithFullCallback()
    {
        $reflection = $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter(UniMapper\Tests\Fixtures\Entity\Simple::stringToArray|UniMapper\Tests\Fixtures\Entity\Simple::arrayToString)');
        Assert::same("foo", $reflection->getName(true));
        Assert::true(is_callable($reflection->getOption(Entity\Reflection\Property::OPTION_MAP_FILTER)[0]));
        Assert::true(is_callable($reflection->getOption(Entity\Reflection\Property::OPTION_MAP_FILTER)[1]));
    }

    /**
     * @throws UniMapper\Exception\PropertyException You must define input/output filter!
     */
    public function testOptionMapFilterInvalidFilter()
    {
        $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter()');
    }

    /**
     * @throws UniMapper\Exception\PropertyException Invalid input filter definition!
     */
    public function testOptionMapFilterInvalidInputFilter()
    {
        $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter(undefinedInputMethod|arrayToString)');
    }

    /**
     * @throws UniMapper\Exception\PropertyException Invalid output filter definition!
     */
    public function testOptionMapFilterInvalidOutputFilter()
    {
        $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter(stringToArray|undefinedOutputMethod)');
    }

    /**
     * @throws UniMapper\Exception\PropertyException Invalid enumeration definition!
     */
    public function testOptionEnumInvalidDefinition()
    {
        $this->_createReflection('string', 'name', 'm:enum(self::ENUMERATION_)');
    }

    /**
     * @throws UniMapper\Exception\PropertyException Enumeration class Undefined not found!
     */
    public function testOptionEnumClassNotFound()
    {
        $this->_createReflection('string', 'name', 'm:enum(Undefined::TYPE_*)');
    }

    public function testOptionEnum()
    {
        $self = $this->_createReflection('string', 'name', 'm:enum(self::ENUMERATION_* )')->getOption(Entity\Reflection\Property::OPTION_ENUM);
        Assert::same(['ENUMERATION_ONE' => 1, 'ENUMERATION_TWO' => 2], $self->getValues());
        Assert::true($self->isValid(1));
        Assert::false($self->isValid(3));

        $class = $this->_createReflection('string', 'name', 'm:enum(' . get_class() . '::ENUM*' . ')')->getOption(Entity\Reflection\Property::OPTION_ENUM);
        Assert::same(['ENUM1' => 1, 'ENUM2' => 2], $class->getValues());
        Assert::true($class->isValid(1));
        Assert::false($class->isValid(3));
    }

}

$testCase = new EntityReflectionPropertyTest;
$testCase->run();