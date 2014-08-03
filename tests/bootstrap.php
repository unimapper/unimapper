<?php

$loader = @include __DIR__ . '/../vendor/autoload.php';
if (!$loader) {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// @todo
//$loader->addPsr4("UniMapper\Tests\Fixtures\\", __DIR__ . "/fixtures");

require __DIR__ . "/fixtures/entity/NoMapper.php";
require __DIR__ . "/fixtures/entity/Simple.php";
require __DIR__ . "/fixtures/entity/Nested.php";
require __DIR__ . "/fixtures/entity/DuplicateProperty.php";
require __DIR__ . "/fixtures/entity/DuplicatePublicProperty.php";
require __DIR__ . "/fixtures/entity/NoPrimary.php";
require __DIR__ . "/fixtures/entity/NoProperty.php";
require __DIR__ . "/fixtures/mapper/Simple.php";
require __DIR__ . "/fixtures/query/Conditionable.php";
require __DIR__ . "/fixtures/query/Simple.php";
require __DIR__ . "/fixtures/repository/SimpleRepository.php";

Tester\Environment::setup();

date_default_timezone_set('Europe/Prague');

$mockista = new \Mockista\Registry;

UniMapper\NamingConvention::$entityMask = "UniMapper\Tests\Fixtures\Entity\*";
UniMapper\NamingConvention::$repositoryMask = "UniMapper\Tests\Fixtures\Repository\*Repository";