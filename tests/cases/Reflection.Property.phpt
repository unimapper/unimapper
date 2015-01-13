<?php

use Tester\Assert,
    UniMapper\EntityCollection,
    UniMapper\Reflection;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ReflectionPropertyTest extends UniMapper\Tests\TestCase
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
        return new Reflection\Property(
            $type,
            $name,
            new Reflection\Entity($entityClass),
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
                new EntityCollection("NoAdapter")
            );

        // Enumeration
        $this->_createReflection('integer', "enum", "m:enum(" . get_class() . "::ENUM*)")
            ->validateValueType(1);
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

    public function testConvertValue()
    {
        // string -> integer
        Assert::same(1, $this->_createReflection('integer',  'id', 'm:primary')->convertValue("1"));

        // integer -> string
        Assert::same("1", $this->_createReflection('string', 'test')->convertValue(1));

        // string -> datetime
        Assert::same(
            "02. 01. 2012",
            $this->_createReflection('DateTime', 'time')
                ->convertValue("2012-02-01")
                ->format("m. d. Y")
        );

        // array -> datetime
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime', 'time')
                ->convertValue(["date" => "2012-02-01"])
        );

        // object -> datetime
        $dateTime = new \stdClass;
        $dateTime->date = "2012-02-01";
        Assert::type(
            "DateTime",
            $this->_createReflection('DateTime', 'time')->convertValue($dateTime)
        );

        // string -> boolean
        Assert::same(
            true,
            $this->_createReflection('boolean', 'true')->convertValue("true")
        );
        Assert::false(
            $this->_createReflection('boolean', 'false')->convertValue("false")
        );

        // array -> collection
        $data = [
            ["url" => "http://example.com"],
            ["url" => "http://johndoe.com"]
        ];
        $collection = $this->_createReflection('Simple[]', 'collection')
            ->convertValue($data);
        Assert::type("UniMapper\EntityCollection", $collection);
        Assert::same(2, count($collection));
        Assert::isEqual("http://example.com", $collection[0]->url);
        Assert::isEqual("http://johndoe.com", $collection[1]->url);

        // array -> entity
        $entity = $this->_createReflection('Simple', 'entity')
            ->convertValue(["id" => "8"]);
        Assert::type("UniMapper\Tests\Fixtures\Entity\Simple", $entity);
        Assert::same(8, $entity->id);
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
                Reflection\Loader::load("Simple")->getFileName(),
                Reflection\Loader::load("Nested")->getFileName(),
                Reflection\Loader::load("Remote")->getFileName()
            ),
            Reflection\Loader::load("Simple")->getRelatedFiles()
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
            Reflection\Property::TYPE_ENTITY,
            $this->_createReflection(
                'Simple', 'entity'
            )->getType()
        );
        Assert::false(
            $this->_createReflection('integer', 'id', 'm:primary')->getType() === Reflection\Property::TYPE_ENTITY
        );
    }

    public function testOptionAssocManyToMany()
    {
        // Local
        $local = $this->_createReflection('Simple[]', 'manyToMany', 'm:assoc(M:N) m:assoc-by(sourceId|source_target|targetId)');
        Assert::type("UniMapper\Association\ManyToMany", $local->getOption(Reflection\Property::OPTION_ASSOC));
        Assert::true($local->hasOption(Reflection\Property::OPTION_ASSOC));
        Assert::false($local->getOption(Reflection\Property::OPTION_ASSOC)->isRemote());
        Assert::same("FooAdapter", $local->getOption(Reflection\Property::OPTION_ASSOC)->getTargetAdapterName());

        // Remote
        $remote = $this->_createReflection('Remote[]', 'manyToMany', 'm:assoc(M:N) m:assoc-by(localId|local_remote|remoteId)')->getOption(Reflection\Property::OPTION_ASSOC);
        Assert::true($remote->isRemote());
        Assert::true($remote->isDominant());
        Assert::same("RemoteAdapter", $remote->getTargetAdapterName());
        Assert::same("localId", $remote->getJoinKey());
        Assert::same("local_remote", $remote->getJoinResource());
        Assert::same("remoteId", $remote->getReferencingKey());
        Assert::same("id", $remote->getTargetPrimaryKey());

        // Remote - not dominant
        $remoteNotDominant = $this->_createReflection('Remote[]', 'manyToMany', 'm:assoc(M<N) m:assoc-by(localId|local_remote|remoteId)');
        Assert::true($remoteNotDominant->getOption(Reflection\Property::OPTION_ASSOC)->isRemote());
        Assert::false($remoteNotDominant->getOption(Reflection\Property::OPTION_ASSOC)->isDominant());
    }

    public function testOptionAssocOneToMany()
    {
        $property = $this->_createReflection('Simple[]', 'oneToMany', 'm:assoc(1:N) m:assoc-by(sourceId)');
        Assert::true($property->hasOption("assoc"));
        Assert::type("UniMapper\Association\OneToMany", $property->getOption(Reflection\Property::OPTION_ASSOC));
    }

    public function testOptionAssocOneToOne()
    {
        $property = $this->_createReflection('Simple', 'oneToOne', 'm:assoc(1:1) m:assoc-by(targetId)');
        Assert::true($property->hasOption("assoc"));
        $association = $property->getOption(Reflection\Property::OPTION_ASSOC);
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
        $association = $property->getOption(Reflection\Property::OPTION_ASSOC);
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
        $reflection = $this->_createReflection('array', 'name', 'm:map-by(foo) m:map-filter(stringToArray|arrayToString)');
        Assert::same("foo", $reflection->getName(true));
        Assert::true(is_callable($reflection->getOption(Reflection\Property::OPTION_MAP_FILTER)[0]));
        Assert::true(is_callable($reflection->getOption(Reflection\Property::OPTION_MAP_FILTER)[1]));
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
        $self = $this->_createReflection('string', 'name', 'm:enum(self::ENUMERATION_* )')->getOption(Reflection\Property::OPTION_ENUM);
        Assert::same(['ENUMERATION_ONE' => 1, 'ENUMERATION_TWO' => 2], $self->getValues());
        Assert::true($self->isValid(1));
        Assert::false($self->isValid(3));

        $class = $this->_createReflection('string', 'name', 'm:enum(' . get_class() . '::ENUM*' . ')')->getOption(Reflection\Property::OPTION_ENUM);
        Assert::same(['ENUM1' => 1, 'ENUM2' => 2], $class->getValues());
        Assert::true($class->isValid(1));
        Assert::false($class->isValid(3));
    }

}

$testCase = new ReflectionPropertyTest;
$testCase->run();