<?php

if (version_compare(phpversion(), '5.4.0', '<')) {
    throw new Exception('UniMapper requires PHP 5.4.0 or newer!');
}

require_once __DIR__ . '/Adapter.php';
require_once __DIR__ . '/Association.php';
require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Convention.php';
require_once __DIR__ . '/Entity.php';
require_once __DIR__ . '/Entity/Collection.php';
require_once __DIR__ . '/Entity/Filter.php';
require_once __DIR__ . '/Entity/Reflection.php';
require_once __DIR__ . '/Entity/Reflection/Annotation.php';
require_once __DIR__ . '/Entity/Reflection/Property.php';
require_once __DIR__ . '/Entity/Reflection/Property/IOption.php';
require_once __DIR__ . '/Entity/Reflection/Property/Option/Assoc.php';
require_once __DIR__ . '/Entity/Reflection/Property/Option/Computed.php';
require_once __DIR__ . '/Entity/Reflection/Property/Option/Enum.php';
require_once __DIR__ . '/Entity/Reflection/Property/Option/Map.php';
require_once __DIR__ . '/Entity/Reflection/Property/Option/Primary.php';
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/Mapper.php';
require_once __DIR__ . '/Query.php';
require_once __DIR__ . '/QueryBuilder.php';
require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Adapter/IAdapter.php';
require_once __DIR__ . '/Adapter/IConvention.php';
require_once __DIR__ . '/Adapter/IQuery.php';
require_once __DIR__ . '/Adapter/Mapping.php';
require_once __DIR__ . '/Association/ManyToMany.php';
require_once __DIR__ . '/Association/ManyToOne.php';
require_once __DIR__ . '/Association/OneToMany.php';
require_once __DIR__ . '/Association/OneToOne.php';
require_once __DIR__ . '/Cache/ICache.php';
require_once __DIR__ . '/Exception/AdapterException.php';
require_once __DIR__ . '/Exception/AnnotationException.php';
require_once __DIR__ . '/Exception/AssociationException.php';
require_once __DIR__ . '/Exception/ConnectionException.php';
require_once __DIR__ . '/Exception/FilterException.php';
require_once __DIR__ . '/Exception/InvalidArgumentException';
require_once __DIR__ . '/Exception/OptionException';
require_once __DIR__ . '/Exception/PropertyException.php';
require_once __DIR__ . '/Exception/QueryException.php';
require_once __DIR__ . '/Exception/ReflectionException.php';
require_once __DIR__ . '/Exception/RepositoryException.php';
require_once __DIR__ . '/Exception/ValidatorException.php';
require_once __DIR__ . '/Query/Count.php';
require_once __DIR__ . '/Query/Delete.php';
require_once __DIR__ . '/Query/DeleteOne.php';
require_once __DIR__ . '/Query/Filterable.php';
require_once __DIR__ . '/Query/Insert.php';
require_once __DIR__ . '/Query/Limit.php';
require_once __DIR__ . '/Query/Select.php';
require_once __DIR__ . '/Query/Selectable.php';
require_once __DIR__ . '/Query/SelectOne.php';
require_once __DIR__ . '/Query/Sortable.php';
require_once __DIR__ . '/Query/Update.php';
require_once __DIR__ . '/Query/UpdateOne.php';
require_once __DIR__ . '/Validator/Condition.php';
require_once __DIR__ . '/Validator/Message.php';
require_once __DIR__ . '/Validator/Rule.php';