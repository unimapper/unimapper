<?php

use Tester\Assert,
    UniMapper\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

// Simple entity
$mapperMock = $mockista->create("UniMapper\Tests\Fixtures\Mapper\Simple");
$mapperMock->expects("insert")->once()->andReturn(1);

$mappers = array();
$mappers["FirstMapper"] = $mapperMock;

$entity = new Fixtures\Entity\Simple;

$query = new Fixtures\Query\Conditionable($entity->getReflection(), $mappers);

$expectedConditions = array();

// where()
$query->where("id", ">", 1);
$expectedConditions[] = array("id", ">", 1, "AND");
Assert::same($expectedConditions, $query->conditions);

// orWhere()
$query->orWhere("text", "=", "foo");
$expectedConditions[] = array("text", "=", "foo", "OR");
Assert::same($expectedConditions, $query->conditions);

// whereAre()
$query->whereAre(function($query) {
    $query->where("id", "<", 2)
          ->orWhere("text", "LIKE", "anotherFoo");
});
$expectedConditions[] = array(
    array(
        array('id', '<', 2, 'AND'),
        array('text', 'LIKE', 'anotherFoo', 'OR')
    ),
    'AND'
);
Assert::same($expectedConditions, $query->conditions);

// orWhereAre()
$query->orWhereAre(function($query) {
    $query->where("id", "<", 5)
          ->orWhere("text", "LIKE", "yetAnotherFoo");
});
$expectedConditions[] = array(
    array(
	array('id', '<', 5, 'AND'),
        array('text', 'LIKE', 'yetAnotherFoo', 'OR'),
    ),
    'OR',
);
Assert::same($expectedConditions, $query->conditions);