<?php

if (version_compare(phpversion(), '5.4.0', '<')) {
    throw new Exception('UniMapper requires PHP 5.4.0 or newer!');
}

require_once __DIR__ . '/Adapter.php';
require_once __DIR__ . '/Connection.php';
require_once __DIR__ . '/Entity.php';
require_once __DIR__ . '/EntityCollection.php';
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/Mapper.php';
require_once __DIR__ . '/Modifier.php';
require_once __DIR__ . '/NamingConvention.php';
require_once __DIR__ . '/Query.php';
require_once __DIR__ . '/QueryBuilder.php';
require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/adapter/IAdapter.php';
require_once __DIR__ . '/adapter/IQuery.php';
require_once __DIR__ . '/association/CollectionModifier.php';
require_once __DIR__ . '/association/EntityModifier.php';
require_once __DIR__ . '/cache/ICache.php';
require_once __DIR__ . '/exception/AdapterException.php';
require_once __DIR__ . '/exception/AnnotationException.php';
require_once __DIR__ . '/exception/AssociationException.php';
require_once __DIR__ . '/exception/DefinitionException.php';
require_once __DIR__ . '/exception/EntityException.php';
require_once __DIR__ . '/exception/InvalidArgumentException';
require_once __DIR__ . '/exception/MappingException.php';
require_once __DIR__ . '/exception/PropertyAccessException.php';
require_once __DIR__ . '/exception/PropertyException.php';
require_once __DIR__ . '/exception/PropertyValueException.php';
require_once __DIR__ . '/exception/QueryException.php';
require_once __DIR__ . '/exception/RepositoryException.php';
require_once __DIR__ . '/exception/UnexpectedException.php';
require_once __DIR__ . '/exception/ValidatorException.php';
require_once __DIR__ . '/query/Count.php';
require_once __DIR__ . '/query/Delete.php';
require_once __DIR__ . '/query/DeleteOne.php';
require_once __DIR__ . '/query/Select.php';
require_once __DIR__ . '/query/SelectOne.php';
require_once __DIR__ . '/query/Insert.php';
require_once __DIR__ . '/query/Selectable.php';
require_once __DIR__ . '/query/Update.php';
require_once __DIR__ . '/query/UpdateOne.php';
require_once __DIR__ . '/reflection/AnnotationParser.php';
require_once __DIR__ . '/reflection/Association.php';
require_once __DIR__ . '/reflection/association/ManyToMany.php';
require_once __DIR__ . '/reflection/association/ManyToOne.php';
require_once __DIR__ . '/reflection/association/OneToMany.php';
require_once __DIR__ . '/reflection/association/OneToOne.php';
require_once __DIR__ . '/reflection/Entity.php';
require_once __DIR__ . '/reflection/Enumeration.php';
require_once __DIR__ . '/reflection/Loader.php';
require_once __DIR__ . '/reflection/Property.php';
require_once __DIR__ . '/validator/Condition.php';
require_once __DIR__ . '/validator/Message.php';
require_once __DIR__ . '/validator/Rule.php';