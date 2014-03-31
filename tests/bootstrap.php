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
require __DIR__ . "/fixtures/entity/Hybrid.php";
require __DIR__ . "/fixtures/mapper/Simple.php";
require __DIR__ . "/fixtures/query/Conditionable.php";
require __DIR__ . "/fixtures/query/Simple.php";
require __DIR__ . "/fixtures/repository/BadConvention.php";
require __DIR__ . "/fixtures/repository/SimpleRepository.php";

Tester\Environment::setup();

$mockista = new \Mockista\Registry;