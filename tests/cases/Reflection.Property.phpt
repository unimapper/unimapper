<?php

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Validate integer
$reflection = new UniMapper\Reflection\Entity\Property(
    '@property integer $id m:map(FirstMapper:) m:primary',
    new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
);
$reflection->validateValue(1);
Assert::exception(function() use ($reflection) {
    $reflection->validateValue("foo");
}, "UniMapper\Exceptions\PropertyTypeException", "Expected integer but string given on property id!");


// Validate string
$reflection = new UniMapper\Reflection\Entity\Property(
    '@property string $text m:map(FirstMapper:)',
    new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
);
$reflection->validateValue("foo");
Assert::exception(function() use ($reflection) {
    $reflection->validateValue(1);
}, "UniMapper\Exceptions\PropertyTypeException", "Expected string but integer given on property text!");


// Validate DateTime
$reflection = new UniMapper\Reflection\Entity\Property(
    '@property DateTime $time',
    new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
);
$reflection->validateValue(new DateTime);
Assert::exception(function() use ($reflection) {
    $reflection->validateValue("foo");
}, "UniMapper\Exceptions\PropertyTypeException", "Expected DateTime but string given on property time!");

// Validate collection
$reflection = new UniMapper\Reflection\Entity\Property(
    '@property UniMapper\Tests\Fixtures\Entity\NoMapper[] $collection',
    new UniMapper\Reflection\Entity("UniMapper\Tests\Fixtures\Entity\Simple")
);
$reflection->validateValue(new UniMapper\EntityCollection("UniMapper\Tests\Fixtures\Entity\Simple"));
Assert::exception(function() use ($reflection) {
    $reflection->validateValue("foo");
}, "UniMapper\Exceptions\PropertyTypeException", "Expected UniMapper\EntityCollection but string given on property collection!");